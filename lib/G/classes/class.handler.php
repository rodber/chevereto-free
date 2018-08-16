<?php

/* --------------------------------------------------------------------

  G\ library
  http://gbackslash.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

/**
 * class.handler.php
 * This class does all the route handling process of the G\ app
 */

namespace G;
use Exception;

class Handler {

	public static $route, $route_request, $route_name, $base_request, $doctitle, $vars, $conds, $routes, $template_used, $prevented_route, $mapped_args;

	/**
	 * Build a valid request
	 */
	function __construct($hook=[]) {

		if(!defined('G_APP_PATH_THEME')) {
			throw new HandlerException('G_APP_PATH_THEME is not defined', 100);
		}

		// Parse the definitions to this object.. This is not necessary but in case of changes...
		$this->relative_root = G_ROOT_PATH_RELATIVE; // nota: realmente necesitamos estos this?
		$this->base_url = G_ROOT_URL;
		$this->path_theme = G_APP_PATH_THEME;

		// Parse the request
		$this->request_uri = $_SERVER['REQUEST_URI'];
		$this->script_name = $_SERVER['SCRIPT_NAME'];

		$query_string = '?' . $_SERVER['QUERY_STRING'];

		if(!empty($_SERVER['QUERY_STRING'])) {
			$this->request_uri = str_replace($query_string, '/', $this->request_uri);
		}

		$this->valid_request = '/' . ltrim(rtrim(sanitize_path_slashes($this->request_uri), '/'), '/');

		if(!empty($_SERVER['QUERY_STRING'])) {
			$this->request_uri = $_SERVER['REQUEST_URI'];
			$this->valid_request .= '/' . $query_string;
		}

		// Store the canonical request, useful for redirect to a valid request
		$this->canonical_request = $this->valid_request;

		if(is_dir(G_ROOT_PATH . $this->valid_request) && $this->valid_request !== '/') {
			$this->canonical_request .= '/';
		}

		$this->handled_request = strtok($this->relative_root == '/' ? $this->valid_request : preg_replace('#' . $this->relative_root . '#', '/', $this->request_uri, 1),'?');
		$this->request_array = explode('/', rtrim(str_replace('//', '/', ltrim($this->handled_request, '/')), '/'));

		// Index request
		if($this->request_array[0] == '') {
			$this->request_array[0] = '/';
		}

		$this->request_array = array_values(array_filter($this->request_array, 'strlen'));
		self::$base_request = $this->request_array[0];

		// Reserved route (index)
		if(self::$base_request == 'index') {
			redirect('/', 301);
		}

		// Fix the canonical request /something?q= to /something/?q=
		if(self::$base_request !== '' && !empty($_SERVER['QUERY_STRING'])) {
			$path_request = add_trailing_slashes(rtrim(str_replace($_SERVER['QUERY_STRING'], '', $this->canonical_request), '?'));
			$fixed_qs_request = $path_request.'?'.$_SERVER['QUERY_STRING'];
			$this->canonical_request = $fixed_qs_request;
		}

		// No /index.php request
		if(self::$base_request == 'index.php') {
			$this->canonical_request = rtrim($this->canonical_request, '/');
			redirect((sanitize_path_slashes(str_replace('index.php', '', $this->canonical_request))), 301);
		}

		// If the request is invalid we make a 301 redirection to the canonical url.
		if($this->relative_root !== $this->request_uri and $this->canonical_request !== $this->request_uri) {
			$this->baseRedirection($this->canonical_request);
		}

		if(in_array(self::$base_request, ['', 'index.php', '/'])) {
			self::$base_request = 'index';
		}

		$this->template = self::$base_request;
		$this->request = $this->request_array;

		self::$route_request = $this->request_array;
		self::$route = $this->template !== 404 ? $this->request_array[0] == '/' ? 'index' : $this->request_array : 404;

		unset($this->request[0]);
		$this->request = array_values($this->request);

		// Hook a fn BEFORE the process
		if(is_array($hook) and is_callable($hook['before'])) {
			$hook['before']($this);
		}

		// It is a valid request on index.php?
		if($this->isIndex()) $this->processRequest();

		// Hook a fn AFTER the process
		if(is_array($hook) and is_callable($hook['after'])) {
			$hook['after']($this);
		}

		// Auto-bind the route vars
		if(is_array(self::$vars)) {
			foreach(self::$vars as $k => $v) {
				$this->bindGetFn($k, $v);
			}
		}
		// Auto-bind the route conditionals
		if(is_array(self::$conds)) {
			foreach(self::$conds as $k => $v) {
				$this->bindIsFn($k, $v);
			}
		}

		$this->loadTemplate();

	}

	/**
	 * Iterate over the route app folder
	 * This populates Handler::$routes with all the valid routes
	 */
	private static function routeIterator($path) {

		if(!file_exists($path)) return;

		foreach(new \DirectoryIterator($path) as $fileInfo) {

			if($fileInfo->isDot() or $fileInfo->isDir()) continue;

			$route_file = $path . $fileInfo->getFilename();
			$route_override = $path . 'overrides/' . $fileInfo->getFilename();

			if(file_exists($route_override)) {
				$route_file = $route_override;
			}

			if(file_exists($route_file)) {
				require_once($route_file);
				$route = array(substr(substr($fileInfo->getFilename(), 0, -4), 6) => $route);
				self::$routes += $route;
			}
		}
	}

	/**
	 * Stock (save) the valid routes of the G\ app
	 * This method is optional because the routeIterator takes some memory
	 */
	public static function stockRoutes() {
		self::$routes = [];
		self::routeIterator(G_APP_PATH_ROUTES);
		self::routeIterator(G_APP_PATH_ROUTES_OVERRIDES);
	}

	/**
	 * Process the dynamic request
	 */
	private function processRequest() {

		if(is_null(self::$routes)) { // Route array is not set
			$route = $this->getRouteFn(self::$base_request);
			if(is_callable($route)) {
				$routes[self::$base_request] = $route; // Build a single $routes array
			}
		} else {
			$routes = self::$routes;
		}

		if(is_array($routes) and array_key_exists(self::$base_request, $routes)) {

			// Autoset some magic
			$magic = array(
				'post'			=> $_POST ? $_POST : NULL,
				'get'			=> $_GET ? $_GET : NULL,
				'request'		=> $_REQUEST ? $_REQUEST : NULL,
				'safe_post'		=> $_POST ? safe_html($_POST) : NULL,
				'safe_get'		=> $_GET ? safe_html($_GET) : NULL,
				'safe_request'	=> $_REQUEST ? safe_html($_REQUEST) : NULL,
				'auth_token' 	=> self::getAuthToken()
			);

			if(self::$vars && count(self::$vars) > 0) {
				self::$vars = array_merge(self::$vars, $magic);
			} else {
				self::$vars = $magic;
			}

			// Only call a valid route fn
			if(!self::$prevented_route and is_callable($routes[self::$base_request])) {
				$routes[self::$base_request]($this);
			}

		} else {
			$this->issue404();
			$this->request = $this->request_array;
		}

		if($this->template == 404) {
			self::$route = 404;
		}
		self::setCond('404', $this->template == 404); // is_404 binding

		if(self::$vars['pre_doctitle']) {
			$stock_doctitle = self::$vars['doctitle'];
			self::$vars['doctitle'] = self::$vars['pre_doctitle'];
			if($stock_doctitle) {
				 self::$vars['doctitle'] .= ' - ' . $stock_doctitle;
			}
		}

		self::$template_used = $this->template;

	}

	/**
	 * Bind route var to global functions
	 */
	public function bindGetFn($var, $value) {
		$fn_name = strtolower(str_replace('-', '_', $var));
		if(!function_exists('get_' . $fn_name)) {
			eval('function get_' . $fn_name . '(){ return G\Handler::$vars["' . $var . '"]; }');
		}
	}

	/**
	 * Bind route conditional to global functions
	 */
	public function bindIsFn($var, $value) {
		$fn_name = strtolower(str_replace('-', '_', $var));
		if(!function_exists('is_' . $fn_name)) {
			eval('function is_' . $fn_name . '(){ return G\Handler::$conds["' . $var . '"]; }');
		}
	}

	/**
	 * Inject the 404 page
	 */
	public function issue404() {
		set_status_header(404);
		if($this->getCond('mapped_route')) {
			self::$base_request = self::$route_request[0];
			self::$route_name = 404;
		}
		$this->template = 404;
	}

	/**
	 * Prevent the rest of the execution loading the target view
	 */
	public function preventRoute($tpl=NULL) {
		if($tpl) {
			$this->template = $tpl;
		}
		self::$prevented_route = true;
	}

	/**
	 * Get the route fn for a given route
	 * If the route doesn't exists it will add it to the routes stack
	 */
	public function getRouteFn($route_name) {
		// Route is already in the stack
		if(is_array(self::$routes) and array_key_exists($route_name, Handler::$routes)) {
			return self::$routes[$route_name];
		}
		// Route doesn't exists in the stack
		$filename = 'route.' . $route_name . '.php';
		$route_file = G_APP_PATH_ROUTES . $filename;
		$route_override_file = G_APP_PATH_ROUTES_OVERRIDES . $filename;
		if(file_exists($route_override_file)) {
			$route_file = $route_override_file;
		}
		if(file_exists($route_file)) {
			require($route_file);
			// Append this new route fn to the Handler::$routes stack
			self::$routes[$route_name] = $route;
			self::$route_name = $route_name;
			return $route;
		} else {
			return false;
		}
	}

	/**
	 * Maps the current route which is useful to make route aliases
	 */
	public function mapRoute($route_name, $args=NULL) {
		$this->template = $route_name;
		self::$base_request = $route_name;
		self::setCond('mapped_route', true);
		if(!is_null($args)) {
			self::$mapped_args = $args;
		}
		return $this->getRouteFn($route_name);
	}

	/**
	 * Return (bool) the request level of the current request
	 */
	public function isRequestLevel($level) {
		return isset($this->request_array[$level - 1]);
	}

	/**
	 * Redirect to the base url/request
	 */
	public function baseRedirection($request) {
    $request = trim(sanitize_path_slashes($request), '/');
		$url = preg_replace('{'.$this->relative_root.'}', '/', $this->base_url, 1) . $request;
		redirect($url, 301);
	}

	/**
	 * Return (bool) if the request is handled by index.php
	 */
	private function isIndex() {
		return preg_match('{/index.php$}', $this->script_name);
	}

	/**
	 * Hook code for loadTemplate()
	 * @args ['code' => '<code>', 'where' => 'before|after']
	 */
	public function hookTemplate($args=[]) {
		if(in_array($args['where'], ['before', 'after']) and $args['code']) {
			if(!isset($this->hook_template)) {
				$this->hook_template = [];
			}
			$this->hook_template[$args['where']] = $args['code'];
		}
	}

	/**
	 * load the setted (or argument) template view
	 */
	private function loadTemplate($template=NULL) {
		if(!is_null($template)) {
			$this->template = $template;
		}

		/** Overrides are loaded from highest to lowest priority **/

		$functions_basename = 'functions.php';
		$template_functions = [
			$this->path_theme . 'overrides/' . $functions_basename,
			$this->path_theme . $functions_basename
		];
		foreach($template_functions as $file) {
			if(file_exists($file)) {
				require_once($file);
				break;
			}
		}

		$view_basename = $this->template;
		$view_extension = get_file_extension($this->template);
		if(!$view_extension) {
			$view_extension = 'php';
			$view_basename .= '.php';
		}
		$template_file = [
			$this->path_theme . 'overrides/views/' . $view_basename,
			$this->path_theme . 'overrides/' . $view_basename,
			$this->path_theme . 'views/'. $view_basename,
			$this->path_theme . $view_basename,
		];
		foreach($template_file as $file) {
			if(file_exists($file)) {
				if($view_extension == 'html') {
					Render\include_theme_header();
				}
				if($this->hook_template['before']) {
					echo $this->hook_template['before'];
				}
				if($view_extension == 'php') {
					require_once($file);
				} else {
					echo file_get_contents($file);
				}
				if($this->hook_template['after']) {
					echo $this->hook_template['after'];
				}
				if($view_extension == 'html') {
					Render\include_theme_footer();
				}
				return;
			}
		}

		$end = end($template_file);
		$key = key($template_file);

		throw new HandlerException('Missing ' . absolute_to_relative($template_file[$key]) . ' template file', 400);
	}

	/**
	 * Returns the 40 char length safe request token
	 */
	public static function getAuthToken() {
		$token = isset($_SESSION['G_auth_token']) ? $_SESSION['G_auth_token'] : random_string(40);
		$_SESSION['G_auth_token'] = $token;
		return $token;
	}

	/**
	 * Checks the integrity and validation of the given request token
	 */
	public static function checkAuthToken($token) {
		if(strlen($token) < 40) return false;
		return timing_safe_compare($_SESSION['G_auth_token'], $token);
	}

	/**
	 * Sets a Handler::$var > get_var() binding
	 */
	public static function setVar($var, $value) {
		self::$vars[$var] = $value;
	}

	/**
	 * Sets a multiple Handler::$var > get_var() binding
	 */
	public static function setVars($array) {
		foreach((array)$array as $var => $value) {
			self::$vars[$var] = $value;
		}
	}

	/**
	 * Sets a Handler::$conds -> is_cond() binding
	 */
	public static function setCond($conds, $bool) {
		self::$conds[$conds] = !$bool ? false : true;
	}

	/**
	 * Sets a multiple Handler::$conds -> is_cond() binding
	 */
	public static function setConds($array=[]) {
		foreach((array)$array as $conds => $bool) {
			self::$conds[$conds] = !$bool ? false : true;
		}
	}

	/**
	 * Get a Handler::$vars[var]
	 */
	public static function getVar($var) {
		return self::getVars()[$var];
	}

	/**
	 * Get all Handler::$vars
	 */
	public static function getVars() {
		return self::$vars;
	}

	/**
	 * Get a Handler::$condss[cond]
	 */
	public static function getCond($cond) {
		return self::getConds()[$cond];
	}

	/**
	 * Get all Handler::$conds
	 */
	public static function getConds() {
		return self::$conds;
	}

	/**
	 * Smart update a Handler::$vars
	 */
	public static function updateVar($var, $value) {
		if(is_array(self::$vars[$var]) and is_array($value)) {
			//self::$vars[$var] = array_merge(self::$vars[$var], $value);
			$value += self::$vars[$var]; // replacement + replaced
			ksort($value);
		}
		self::$vars[$var] = $value;
	}

	/**
	 * Unset a given var
	 */
	public static function unsetVar($var) {
		unset(self::$vars[$var]);
	}

	/**
	 * Get the template file basename used
	 */
	public static function getTemplateUsed() {
		return self::$template_used;
	}

	/**
	 * Get the current route path
	 * @args $full (bool true) outputs the full route 'like/this' or 'this'
	 */
	public static function getRoutePath($full=true) {
		if(is_array(self::$route)) {
			return $full ? implode('/', self::$route) : self::$route[0];
		} else {
			return self::$route;
		}
 	}

	/**
	 * Get the current route name from route.name.php
	 */
	public static function getRouteName() {
		return self::$route_name;
 	}

}

class HandlerException extends Exception {}