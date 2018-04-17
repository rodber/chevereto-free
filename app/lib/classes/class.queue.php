<?php

/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
			<inbox@rodolfoberrios.com>

  Copyright (C) Rodolfo Berrios A. All rights reserved.
  
  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */

namespace CHV;
use G, Exception;

class Queue {
	
	public static $max_execution_time;
	
	public static function insert($values) {
		try {
			$values = array_merge([
				'date_gmt'	=> G\datetimegmt(),
				'status'	=> 'pending'
			], $values);
			DB::insert('queues', $values);
		} catch(Exception $e) {
			throw new QueueException($e->getMessage(), $e->getCode());
		}
	}
	
	public static function process($args) {
		try {
			@set_time_limit(180); // Don't run forever
			self::$max_execution_time = ini_get('max_execution_time'); // Store the limit
				
			$args = array_merge(['pixel' => true, $type => NULL], $args);
			
			if($args['pixel']) {
				error_reporting(0); // Silence when embeding an image
			}

			// Get 250 entries (casual limit)
			$queues_db = DB::get(['table' => 'queues', 'join' => 'LEFT JOIN ' . DB::getTable('storages') . ' ON ' . DB::getTable('queues') .'.queue_join = '. DB::getTable('storages') . '.storage_id'], ['type' => $args['type'], 'status' => 'pending'], 'AND', NULL, 250);
			$queues = [];
			
			foreach($queues_db as $k => $v) {
				$queue_item = DB::formatRow($v);
				$queue_item['args'] = json_decode($queue_item['args'], true);
				// Group the things by storage
				if(!array_key_exists($queue_item['storage']['id'], $queues)) {
					$queues[$queue_item['storage']['id']] = ['storage' => $queue_item['storage'], 'files' => []];
				}
				$queues[$queue_item['storage']['id']]['files'][] = G\array_filter_array($queue_item, ['id', 'args'], 'exclusion');
			}
			
			foreach($queues as $k => $storage_queue) {
				
				// Break the thing if its close to the time limit
				if(!self::canKeepGoing()) {
					break;
				}
				
				$storage = $storage_queue['storage'];
				$storage_files = $storage_queue['files'];
				$storage['api_type'] = Storage::getApiType($storage['api_id']);
				
				$files = [];
				$storage_keys = [];
				$deleted_queue_ids = [];
				$disk_space_freed = 0;
				$disk_space_used = 0;
				
				// Localize the array 'key'
				foreach($storage_files as $k => $v) {
					$files[$v['args']['key']] = array_merge($v['args'], ['id' => $v['id']]);
					switch($storage['api_type']) {
						case 's3':
							$storage_keys[] = ['Key' => $v['args']['key']];
						break;
						case 'gcloud':
						case 'ftp':
						case 'sftp':
							$storage_keys[] = $v['args']['key'];
						break;
						case 'openstack':
							$storage_keys[] = $storage['bucket'] . '/' . $v['args']['key'];
						break;
					}
					unset($files[$k]);
					$disk_space_used += $v['args']['size'];
					$deleted_queue_ids[] = $v['id']; // Generate the queue_id stock
				}
				
				$error = FALSE;
				
				// Invoke the target storage API
				try {
					$StorageAPI = Storage::requireAPI($storage);
				} catch(Exception $e) {
					self::logAttempt($deleted_queue_ids);
					error_log($e);
					$error = TRUE;
					break;
				}
				
				switch($storage['api_type']) {
				
					case 's3':
						try {
							$deleteFromStorage = $StorageAPI->deleteObjects([
								'Bucket'	=> $storage['bucket'],
								'Delete'	=> [
									'Objects'	=> $storage_keys
								]
							]);
						} catch(Exception $e) {
							error_log($e);
							$error = TRUE;
							break;
						}
						$deleted_queue_ids = []; // Just in case
						foreach($deleteFromStorage['Deleted'] as $k => $v) {
							$disk_space_freed += $files[$v['Key']]['size'];
							$deleted_queue_ids[] = $files[$v['Key']]['id'];
						}
					break;
					
					case 'openstack':
						try {
							$deleteFromStorage = $StorageAPI->batchDelete($storage_keys);
						} catch(Exception $e) {
							error_log($e);
							$error = TRUE;
							break;
						}
					break;
					
					// AKA single file methods
					case 'gcloud':
					case 'ftp':
						foreach($files as $k => $v) { // No batch operation here
							if(!self::canKeepGoing()) { // Time safe
								break;
							}
							try {
								switch($storage['api_type']) {
									case 'gcloud':
										$StorageAPI->objects->delete($storage['bucket'], $v['key']);
									break;
									case 'ftp':
										$StorageAPI->delete($v['key']);
									break;
								}
								$deleted_queue_ids[] = $v['id'];
								$disk_space_freed += $v['size'];
							} catch(Exception $e) {
								error_log($e->getCode() . ' - ' . $e->getMessage());
							}
						}
						if($storage['api_type'] == 'ftp') {
							$StorageAPI->close(); // Close FTP
						}
					break;
					
					case 'sftp':
						// This thing uses direct rm command (wow, such raw)
						$StorageAPI->deleteMultiple($storage_keys);
						$disk_space_freed = $disk_space_used;
						$StorageAPI->close(); // Close SFTP
					break;
					
				}
				
				self::logAttempt($deleted_queue_ids);
				
				if(!$error) {
					DB::increment('storages', ['space_used' => '-' . $disk_space_freed], ['id' => $storage['id']]);
					self::delete($deleted_queue_ids);
				}

			}
			
		} catch(Exception $e) {
			if($args['pixel']) {
				error_log($e);
			} else {
				throw new QueueException($e);
			}
		}
	}
	
	public static function delete($ids) {
		try {
			if(is_array($ids)) {
				$db = DB::getInstance();
				$db->query('DELETE from ' . DB::getTable('queues') . ' WHERE queue_id IN (' . implode(',', $ids) . ')');
				return $db->exec() ? $db->rowCount() : false;
			} else {
				return DB::delete('queues', ['id' => $ids]) ? 1 : false;
			}
		} catch(Exception $e) {
			throw new QueueException($e);
		}
	}

	public static function logAttempt($ids) {
		try {
			if(!is_array($ids)) {
				$ids = [$ids];
			}
			$db = DB::getInstance();
			$db->query('UPDATE ' . DB::getTable('queues') . ' SET queue_attempts = queue_attempts + 1, queue_status = IF(queue_attempts > 3, "failed", "pending") WHERE queue_id IN (' . implode(',', $ids) . ')');
			$db->exec();
		} catch(Exception $e) {
			throw new QueueException($e);
		}
	}
	
	public static function canKeepGoing() {
		return isSafeToExecute(self::$max_execution_time);
	}
}

class QueueException extends Exception {}
