$(document).ready(function(){
    setInterval(function() {
        var $el = $(".animated-ellipsis");
        var ellipsis = $el.html();
        ellipsis = ellipsis + ".";
        if(ellipsis.length > 3) {
            ellipsis = "";
        }
        $el.html(ellipsis);
    }, 400);
    // The auto-upgrade procedure
    update.process();    
});
    
var update = {
    vars: {},
    process: function(callback) { // S0: Check if update is needed
        var _this = this;
        _this.addLog(PF.fn._s("Installed version is v%s", vars.current_version));
        $.ajax({
            url: vars.url,
            data: {action: "ask"}
        })
        .always(function(data, status, XHR) {
            if(!XHR) {
                _this.abort(PF.fn._s("Can't connect to %s", vars.url), 400);
                return;
            }
            if(data.status_code == 200) {
                _this.addLog(PF.fn._s("Last available release is v%s", data.software.current_version));
                if(PF.fn.versionCompare(vars.current_version, data.software.current_version) == -1) { // Can update
                    _this.vars.target_version = data.software.current_version;
                    _this.addLog(PF.fn._s("Update needed, proceeding to download"));
                    _this.download(function() {
                        _this.extract(function() {
                            _this.install(); // yo dawg
                        });
                    });
                } else {
                    $("h1").html(PF.fn._s("No update needed"));
                    _this.addLog(PF.fn._s("System files already up to date", vars.current_version));
                    _this.install();
                }
            }
        });
    },
    download: function(callback) {
        var _this = this;
        _this.addLog(PF.fn._s("Starting v%s download", this.vars.target_version));
        $.ajax({
            url: vars.url,
            data: {action: "download", version: _this.vars.target_version},
        }).always(function(data, status, XHR) {
            if(!XHR) {
                _this.abort(PF.fn._s("Can't connect to %s", vars.url), 400);
                return;
            }
            if(data.status_code == 200) {
                _this.vars.target_filename = data.download.filename;
                _this.addLog(PF.fn._s("Downloaded v%s, proceeding to extraction", _this.vars.target_version));
                if(typeof callback == "function") {
                    callback();
                }
            } else {
                _this.abort(data.responseJSON.error.message, 400);
            }
        });
        
    },
    extract: function(callback) {
        var _this = this;
        _this.addLog(PF.fn._s("Attempting to extract v%s", this.vars.target_version));
        $.ajax({
            url: vars.url,
            data: {action: "extract", file: _this.vars.target_filename},
        }).always(function(data, status, XHR) {
            if(!XHR) {
                _this.abort(PF.fn._s("Can't connect to %s", vars.url), 400);
                return;
            }
            if(data.status_code == 200) {
                _this.addLog(PF.fn._s("Extraction completed", _this.vars.target_version));
                setTimeout(function() {
                    _this.addLog(PF.fn._s("Proceding to install the update", _this.vars.target_version));
                    if(typeof callback == "function") {
                        callback();
                    }
                }, 500);
            } else {
                _this.abort(data.responseJSON.error.message, 400);
            }
        });
    },
    install: function() {
        var _this = this;
        setTimeout(function() {
            window.location = PF.obj.config.base_url + "/install";
        }, 2000);
    },
    addLog: function(message, code) {
        if(!code) code = 200;
        var $el = $("ul");
        var d = PF.fn.getDateTime().substring(11);
        var $event = $("<li/>", {
            class: code != 200 ? 'color-red' : null,
            text: d + ' ' + message
        });
        $el.prepend($event);
    },
    abort: function(message) {
        $("h1").html(PF.fn._s("Update failed"));
        if(message) {
            this.addLog(message, 400);
        }
    }
};