/* --------------------------------------------------------------------

  Chevereto Popup Upload Plugin (PUP)

  @website	http://chevereto.com/
  @version	1.0.7
  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  --------------------------------------------------------------------- */
  (function () {
    var PUP = {
        defaultSettings: {
            url: "https://demo.chevereto.com/upload",
            vendor: "auto",
            mode: "auto",
            lang: "auto",
            autoInsert: "bbcode-embed-medium",
            palette: "default",
            init: "onload",
            containerClass: 1,
            buttonClass: 1,
            sibling: 0,
            siblingPos: "after",
            fitEditor: 0,
            observe: 0,
            observeCache: 1,
            html:
                '<div class="%cClass"><button %x class="%bClass"><span class="%iClass">%iconSvg</span><span class="%tClass">%text</span></button></div>',
            css:
                ".%cClass{display:inline-block;margin-top:5px;margin-bottom:5px}.%bClass{line-height:normal;-webkit-transition:all .2s;-o-transition:all .2s;transition:all .2s;outline:0;color:%2;border:none;cursor:pointer;border:1px solid rgba(0,0,0,.15);background:%1;border-radius:.2em;padding:.5em 1em;font-size:12px;font-weight:700;text-shadow:none}.%bClass:hover{background:%3;color:%4;border-color:rgba(0,0,0,.1)}.%iClass,.%tClass{display:inline-block;vertical-align:middle}.%iClass svg{display:block;width:1em;height:1em;fill:currentColor}.%tClass{margin-left:.25em}"
        },
        ns: {
            plugin: "chevereto-pup"
        },
        palettes: {
            default: ["#ececec", "#333", "#2980b9", "#fff"],
            clear: ["inherit", "inherit", "inherit", "#2980b9"],
            turquoise: ["#16a085", "#fff", "#1abc9c", "#fff"],
            green: ["#27ae60", "#fff", "#2ecc71", "#fff"],
            blue: ["#2980b9", "#fff", "#3498db", "#fff"],
            purple: ["#8e44ad", "#fff", "#9b59b6", "#fff"],
            darkblue: ["#2c3e50", "#fff", "#34495e", "#fff"],
            yellow: ["#f39c12", "#fff", "#f1c40f", "#fff"],
            orange: ["#d35400", "#fff", "#e67e22", "#fff"],
            red: ["#c0392b", "#fff", "#e74c3c", "#fff"],
            grey: ["#ececec", "#000", "#e0e0e0", "#000"],
            black: ["#333", "#fff", "#666", "#fff"]
        },
        classProps: ["button", "container"],
        iconSvg:
            '<svg class="%iClass" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M76.7 87.5c12.8 0 23.3-13.3 23.3-29.4 0-13.6-5.2-25.7-15.4-27.5 0 0-3.5-0.7-5.6 1.7 0 0 0.6 9.4-2.9 12.6 0 0 8.7-32.4-23.7-32.4 -29.3 0-22.5 34.5-22.5 34.5 -5-6.4-0.6-19.6-0.6-19.6 -2.5-2.6-6.1-2.5-6.1-2.5C10.9 25 0 39.1 0 54.6c0 15.5 9.3 32.7 29.3 32.7 2 0 6.4 0 11.7 0V68.5h-13l22-22 22 22H59v18.8C68.6 87.4 76.7 87.5 76.7 87.5z" style="fill: currentcolor;"/></svg>',
        l10n: {
            ar: "\u062a\u062d\u0645\u064a\u0644 \u0627\u0644\u0635\u0648\u0631",
            cs: "Nahr\u00e1t obr\u00e1zky",
            da: "Upload billeder",
            de: "Bilder hochladen",
            es: "Subir im\u00e1genes",
            fi: "Lataa kuvia",
            fr: "Importer des images",
            id: "Unggah gambar",
            it: "Carica immagini",
            ja: "\u753b\u50cf\u3092\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9",
            nb: "Last opp bilder",
            nl: "Upload afbeeldingen",
            pl: "Wy\u015Blij obrazy",
            pt_BR: "Enviar imagens",
            ru:
                "\u0417\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044c \u0438\u0437\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0438\u044f",
            tr: "Resim Yukle",
            uk:
                "\u0417\u0430\u0432\u0430\u043D\u0442\u0430\u0436\u0438\u0442\u0438 \u0437\u043E\u0431\u0440\u0430\u0436\u0435\u043D\u043D\u044F",
            zh_CN: "\u4e0a\u4f20\u56fe\u7247",
            zh_TW: "\u4e0a\u50b3\u5716\u7247"
        },
        vendors: {
            default: {
                check: function () {
                    return 1;
                },
                getEditor: function () {
                    var skip = {
                        textarea: {
                            name: [
                                "recaptcha",
                                "search",
                                "recipients",
                                "coppa",
                                "^comment_list",
                                "username_list",
                                "add"
                            ]
                        },
                        ce: {
                            dataset: ["gramm"]
                        }
                    };
                    var mods = ["~", "|", "^", "$", "*"];
                    var not = {};
                    for (var k in skip) {
                        not[k] = "";
                        var el = skip[k];
                        for (var attr in el) {
                            for (var i = 0; i < el[attr].length; i++) {
                                var mod = "";
                                var value = el[attr][i];
                                var f = value.charAt(0);
                                if (mods.indexOf(f) > -1) {
                                    mod = f;
                                    value = value.substring(1);
                                }
                                not[k] +=
                                    ":not([" +
                                    (attr == "dataset"
                                        ? "data-" + value
                                        : attr + mod + '="' + value + '"') +
                                    "])";
                            }
                        }
                    }
                    return document.querySelectorAll(
                        '[contenteditable=""]' +
                        not.ce +
                        ',[contenteditable="true"]' +
                        not.ce +
                        ",textarea:not([readonly])" +
                        not.textarea
                    );
                }
            },
            bbpress: {
                settings: {
                    autoInsert: "html-embed-medium",
                    html:
                        '<input %x type="button" class="ed_button button button-small" aria-label="%text" value="%text">',
                    sibling: "#qt_bbp_reply_content_img",
                    siblingPos: "before"
                },
                check: "bbpEngagementJS"
            },
            discourse: {
                settings: {
                    autoInsert: "markdown-embed-medium",
                    html:
                        '<button %x title="%text" class="upload btn no-text btn-icon ember-view"><i class="fa fa-cloud-upload d-icon d-icon-upload"></i></button>',
                    sibling: ".upload.btn",
                    siblingPos: "before",
                    observe: ".create,#create-topic,.usercard-controls button",
                    observeCache: 0,
                    onDemand: 1
                },
                check: "Discourse"
            },
            discuz: {
                settings: {
                    buttonClass: 1,
                    html: '<a %x title="%text" class="%bClass">%iconSvg</a>',
                    sibling: ".fclr,#e_attach",
                    css:
                        "a.%bClass,.bar a.%bClass{box-sizing:border-box;cursor:pointer;background:%1;color:%2;text-indent:unset;position:relative}.b1r a.%bClass:hover,a.%bClass:hover{background:%3;color:%4}a.%bClass{font-size:14px}.b1r a.%bClass{border:1px solid rgba(0,0,0,.15)!important;font-size:20px;padding:0;height:44px}.%bClass svg{font-size:1em;width:1em;height:1em;-webkit-transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%);transform:translate(-50%,-50%);position:absolute;left:50%;top:50%;fill:currentColor}",
                    palette: "purple"
                },
                palettes: {
                    default: ["transparent", "#333", "#2980b9", "#fff"]
                },
                check: "DISCUZCODE",
                getEditor: function () {
                    return document.querySelector('.area textarea[name="message"]');
                }
            },
            ipb: {
                settings: {
                    autoInsert: "html-embed-medium",
                    html:
                        '<a %x class="cke_button cke_button_off %bClass" title="%text" tabindex="-1" hidefocus="true" role="button"><span class="cke_button_icon">%iconSvg</span><span class="cke_button_label" aria-hidden="false">%text</span><span class="cke_button_label" aria-hidden="false"></span></a>',
                    sibling: ".cke_button__ipslink",
                    siblingPos: "before",
                    css:
                        ".cke_button.%bClass{background:%1;position:relative}.cke_button.%bClass:hover{background:%3;border-color:%5}.cke_button.%bClass svg{font-size:15px;width:1em;height:1em;-webkit-transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%);transform:translate(-50%,-50%);position:absolute;left:50%;top:50%;fill:%2}.cke_button.%bClass:hover svg{fill:%4}"
                },
                palettes: {
                    default: ["inherit", "#444", "", "inherit"]
                },
                check: "ips",
                getEditorFn: function () {
                    var id = this.getEditor().dataset.ipseditorName;
                    return CKEDITOR.instances[id];
                },
                getEditor: function () {
                    return document.querySelector("[data-ipseditor-name]");
                },
                editorValue: function (str) {
                    var element = CKEDITOR.dom.element.createFromHtml(
                        "<p>" + str + "</p>"
                    );
                    this.getEditorFn().insertElement(element);
                },
                useCustomEditor: function () {
                    return 1;
                }
            },
            mybb: {
                settings: {
                    sibling:
                        "#quickreply_e > tr > td > *:last-child, .sceditor-container",
                    fitEditor: 0,
                    extracss: ".trow2 .%cClass{margin-bottom:0}"
                },
                check: "MyBB",
                getEditor: function () {
                    if (MyBBEditor) {
                        return MyBBEditor.getContentAreaContainer().parentElement;
                    }
                    return document.querySelector("#quickreply_e textarea");
                },
                editorValue: function (str) {
                    if (MyBBEditor) {
                        var fn = MyBBEditor.inSourceMode()
                            ? "insert"
                            : "wysiwygEditorInsertHtml";
                        MyBBEditor[fn](fn == "insert" ? str : MyBBEditor.fromBBCode(str));
                    } else {
                        this.getEditor().value += str;
                    }
                },
                useCustomEditor: function () {
                    return !!MyBBEditor;
                }
            },
            nodebb: {
                settings: {
                    autoInsert: "markdown-embed-medium",
                    html:
                        '<li %x tabindex="-1" title="%text"><i class="fa fa-cloud-upload"></i></li>',
                    sibling: '[data-format="picture-o"]',
                    siblingPos: "before",
                    observe:
                        '[component="category/post"],[component="topic/reply"],[component="topic/reply-as-topic"],[component="post/reply"],[component="post/quote"]',
                    observeCache: 0,
                    onDemand: 1
                },
                check: "__nodebbSpamBeGoneCreateCaptcha__",
                callback: function () {
                    var els = document.querySelectorAll(".btn-toolbar .img-upload-btn");
                    for (var i = 0; i < els.length; i++) {
                        els[i].parentNode.removeChild(els[i]);
                    }
                }
            },
            phpbb: {
                settings: {
                    html:
                        document.querySelector("#format-buttons *:first-child") &&
                            document.querySelector("#format-buttons *:first-child").tagName ==
                            "BUTTON"
                            ? ' <button %x type="button" class="button button-icon-only" title="%text"><i class="icon fa-cloud-upload fa-fw" aria-hidden="true"></i></button> '
                            : ' <input %x type="button" class="button2" value="%text"> ',
                    sibling: document.querySelector("#format-buttons *:first-child") && document.querySelector("#format-buttons *:first-child").tagName ==
                    "BUTTON" ? ".bbcode-img" : "#message-box textarea.inputbox",
                    siblingPos: "after"
                },
                check: "phpbb",
                getEditor: function () {
                    if (
                        typeof form_name == typeof undefined ||
                        typeof text_name == typeof undefined
                    ) {
                        return;
                    }
                    return document.forms[form_name].elements[text_name];
                }
            },
            proboards: {
                settings: {
                    html: ' <input %x type="submit" value="%text"> ',
                    css: "",
                    sibling: "input[type=submit]",
                    siblingPos: "before"
                },
                check: "proboards",
                editorValue: function (str) {
                    var wysiwyg = $(".wysiwyg-textarea").data("wysiwyg");
                    var editor = wysiwyg.editors[wysiwyg.currentEditorName];
                    editor.setContent(editor.getContent() + str);
                },
                useCustomEditor: function () {
                    return $(".container.quick-reply").size() !== 1;
                },
                getEditor: function () {
                    return document.querySelector("textarea[name=message]");
                }
            },
            redactor2: {
                getEditor: function () {
                    var editor = this.getEditorFn();
                    if (!editor) {
                        return null;
                    }
                    return !this.useCustomEditor() ? editor[0] : editor.$box[0];
                },
                getEditorEl: function () {
                    return this.useCustomEditor()
                        ? this.getEditorFn().$editor[0]
                        : this.getEditorFn()[0];
                },
                editorValue: function (str) {
                    var nl = "<p><br></p>";
                    var property = this.useCustomEditor() ? "innerHTML" : "value";
                    if (typeof str == "string") {
                        if (this.useCustomEditor()) {
                            var insert = "<p>" + str + "</p>";
                            this.getEditorFn().insert.html(
                                this.editorValue() !== "" ? nl + insert : insert
                            );
                        } else {
                            this.getEditorEl()[property] = str;
                        }
                        return;
                    }
                    var value = this.getEditorEl()[property];
                    if (this.useCustomEditor() && value == "<p><br></p>") {
                        return "";
                    }
                    return this.getEditorEl()[property];
                },
                useCustomEditor: function () {
                    return !(this.getEditorFn() instanceof jQuery);
                }
            },
            smf: {
                settings: {
                    html:
                        ' <button %x title="%text" class="%bClass"><span class="%iClass">%iconSvg</span><span class="%tClass">%text</span></button> ',
                    css:
                        "%defaultCSS #bbcBox_message .%bClass{margin-right:1px;transition:none;color:%2;padding:0;width:23px;height:21px;border-radius:5px;background-color:%1}#bbcBox_message .%bClass:hover{background-color:%3}#bbcBox_message .%tClass{display:none}",
                    sibling: "#BBCBox_message_button_1_1,.quickReplyContent + div",
                    siblingPos: "before",
                    fitEditor: 1
                },
                palettes: {
                    default: ["#E7E7E7", "#333", "#B0C4D6", "#333"]
                },
                check: "smf_scripturl",
                getEditor: function () {
                    return smf_editorArray.length > 0
                        ? smf_editorArray[0].oTextHandle
                        : document.querySelector(".quickReplyContent textarea");
                }
            },
            "quill": {
                settings: {
                    autoInsert: "html-embed-medium",
                    html:
                        '<li class="richEditor-menuItem richEditor-menuItem_f1af88yq" role="menuitem"><button %x class="richEditor-button richEditor-embedButton richEditor-button_f1fodmu3" type="button" aria-pressed="false"><span class="richEditor-iconWrap_f13bdese"></span>%iconSvg</button></li>',
                    sibling: "ul.richEditor-menuItems li.richEditor-menuItem:last-child",
                    css: ".%iClass {display: block; height: 24px; margin: auto; width: 24px;}"
                },
                check: "quill",
                editorValue: function (str) {
                    quill.clipboard.dangerouslyPasteHTML(
                        quill.getText() == "\n" ? 0 : quill.getLength(),
                        str
                    );
                },
                useCustomEditor: function () {
                    return 1;
                },
                getEditor: function () {
                    return quill.container;
                }
            },
            vanilla: {
                settings: {
                    autoInsert: "markdown-embed-medium",
                    html: '<span %x class="icon icon-cloud-upload" title="%text"></span>',
                    sibling: ".editor-dropdown-upload"
                },
                check: "Vanilla",
                getEditor: function () {
                    return document.getElementById("Form_Body");
                }
            },
            vbulletin: {
                settings: {
                    autoInsert: "html-embed-medium",
                    html:
                        '<li %x class="%bClass b-toolbar__item b-toolbar__item--secondary" title="%text" tabindex="0">%iconSvg</li>',
                    sibling: ".b-toolbar__item--secondary:first-child",
                    siblingPos: "before",
                    css:
                        ".%bClass{background:%1;color:%2;position:relative}.%bClass:hover{background:%3;color:%4;border-color:%5}.%bClass svg{font-size:15px;width:1em;height:1em;-webkit-transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%);transform:translate(-50%,-50%);position:absolute;left:50%;top:50%;fill:currentColor}"
                },
                palettes: {
                    default: ["", "#4B6977", "", "#007EB8"]
                },
                check: "vBulletin",
                getEditorFn: function () {
                    var id = this.getEditor().getAttribute("ck-editorid");
                    return CKEDITOR.instances[id];
                },
                getEditor: function () {
                    return document.querySelector("[data-message-type]");
                },
                editorValue: function (str) {
                    var element = CKEDITOR.dom.element.createFromHtml(
                        "<p>" + str + "</p>"
                    );
                    this.getEditorFn().insertElement(element);
                },
                useCustomEditor: function () {
                    return 1;
                }
            },
            // Redactor
            // At some point,
            WoltLab: {
                settings: {
                    autoInsert: "html-embed-medium",
                    sibling: 'li[data-name="settings"]',
                    html:
                        '<li %x><a><span class="icon icon16 fa-cloud-upload"></span> <span>%text</span></a></li>'
                },
                check: "WBB",
                getEditorFn: function () {
                    var redactor = $("#text").data("redactor");
                    if (redactor) {
                        return redactor;
                    }
                    return null;
                }
            },
            // Redactor
            XF1: {
                settings: {
                    autoInsert: "html-embed-medium",
                    containerClass: 1,
                    buttonClass: 1,
                    html:
                        '<li class="%cClass"><a %x class="%bClass" unselectable="on" title="%text">%iconSvg</a></li>',
                    sibling: ".redactor_btn_container_image",
                    siblingPos: "before",
                    css:
                        "li.%cClass .%bClass{background:%1;color:%2;text-indent:unset;border-radius:3px;position:relative}li.%cClass a.%bClass:hover{background:%3;color:%4;border-color:%5}.%cClass .%bClass svg{font-size:15px;width:1em;height:1em;-webkit-transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%);transform:translate(-50%,-50%);position:absolute;left:50%;top:50%;fill:currentColor}",
                    observe: ".edit.OverlayTrigger",
                    observeCache: 1
                },
                palettes: {
                    default: ["none", "inherit", "none", "inherit", ""]
                },
                check: "XenForo",
                getEditorFn: function () {
                    var form =
                        document.querySelector("#exposeMask") &&
                            document.querySelector("#exposeMask").offsetParent
                            ? ".xenOverlay form"
                            : "form";
                    if (form !== "form") {
                        var forms = document.querySelectorAll(form);
                        for (var i = 0; i < forms.length; i++) {
                            if (forms[i].offsetParent) {
                                form += '[action="' + forms[i].getAttribute("action") + '"]';
                                break;
                            }
                        }
                    }
                    return XenForo.getEditorInForm(form);
                },
                getEditor: function () {
                    var editor = this.getEditorFn();
                    if (!editor) {
                        return null;
                    }
                    return !this.useCustomEditor() ? editor[0] : editor.$box[0];
                },
                getEditorEl: function () {
                    return this.useCustomEditor()
                        ? this.getEditorFn().$editor[0]
                        : this.getEditorFn()[0];
                },
                editorValue: function (str) {
                    var nl = "<p><br></p>";
                    var property = this.useCustomEditor() ? "innerHTML" : "value";
                    if (typeof str == "string") {
                        if (this.useCustomEditor()) {
                            var insert = "<p>" + str + "</p>";
                            this.getEditorFn().insertHtml(
                                this.editorValue() !== "" ? nl + insert : insert
                            );
                        } else {
                            this.getEditorEl()[property] = str;
                        }
                        return;
                    }
                    var value = this.getEditorEl()[property];
                    if (this.useCustomEditor() && value == "<p><br></p>") {
                        return "";
                    }
                    return this.getEditorEl()[property];
                },
                useCustomEditor: function () {
                    return !(this.getEditorFn() instanceof jQuery);
                }
            },
            // Froala
            XF2: {
                settings: {
                    autoInsert: "html-embed-medium",
                    containerClass: 1,
                    buttonClass: "button--link js-attachmentUpload button button--icon button--icon--upload fa--xf",
                    html: '<div class="formButtonGroup"><div class="formButtonGroup-extra"><button type="button" tabindex="-1" role="button" title="%text" class="%bClass" %x><span class="button-text">%text</span></button></div></div>',
                    sibling: '',
                    siblingPos: "after",
                    observe: '[data-xf-click="quick-edit"]',
                    observeCache: 1
                },
                palettes: {
                    default: ["transparent", "#505050", "rgba(20,20,20,0.06)", "#141414"]
                },
                check: "XF",
                // Use id to pass the target editor, if not... XF editor will always return the first editor (DOM) for .js-editor selector
                getEditorFn: function (id) {
                    var sel = ".js-editor";
                    if (typeof id == "string") {
                        sel = this.getEditorSel(id);
                    }
                    return XF.getEditorInContainer($(sel));
                },
                getEditorSel: function (id) {
                    return "[" + PUP.ns.dataPluginTarget + '="' + id + '"]';
                },
                getEditor: function (id) {
                    if (typeof id == "string") {
                        return document.querySelector(this.getEditorSel(id));
                    }
                    return document.querySelectorAll(".js-editor");
                },
                getBbCode: function (edFnCode) {
                    return edFnCode.getTextArea()[0].value;
                },
                editorValue: function (str, id) {
                    var nl = "<p><br></p>";
                    var edFn = this.getEditorFn(id);
                    var type = edFn.ed.bbCode.isBbCodeView()
                        ? ["bbCode", "getBbCode", "insertBbCode"]
                        : ["html", "get", "insert"];
                    var edFnCode = edFn.ed[type[0]];
                    if (typeof str == "string") {
                        var jump = this.editorValue(false, id) !== "";
                        if (type[0] == "html") {
                            var insert = "<p>" + str + "</p>";
                            edFnCode[type[2]](jump ? nl + insert : insert);
                        } else {
                            var XHR = XF.ajax(
                                "POST",
                                XF.canonicalizeUrl("index.php?editor/to-bb-code"),
                                {
                                    html: str
                                }
                            );
                            XHR.done(function (data) {
                                edFnCode[type[2]](jump ? "\n" + data.bbCode : data.bbCode);
                            });
                        }
                        return;
                    }
                    if (typeof edFnCode[type[1]] == typeof undefined) {
                        var value = this.getBbCode(edFnCode);
                    } else {
                        var value = edFnCode[type[1]]();
                    }
                    if (this.useCustomEditor() && value == nl) {
                        return "";
                    }
                    return value;
                },
                useCustomEditor: function () {
                    return (
                        typeof XF.getEditorInContainer($(".js-editor")) !== typeof undefined
                    );
                }
            }
        },
        generateGuid: function () {
            var d = new Date().getTime();
            if (
                typeof performance !== "undefined" &&
                typeof performance.now === "function"
            ) {
                d += performance.now();
            }
            return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (
                c
            ) {
                var r = (d + Math.random() * 16) % 16 | 0;
                d = Math.floor(d / 16);
                return (c === "x" ? r : (r & 0x3) | 0x8).toString(16);
            });
        },
        getNewValue: function (el, msg) {
            var prop =
                typeof el.getAttribute("contenteditable") !== "string"
                    ? "value"
                    : "innerHTML";
            var newline = prop == "value" ? "\n" : "<br>";
            var value = el[prop];
            var fixed = msg;
            var escape = false;
            if (escape) {
                fixed = String(msg)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;");
            }
            if (value.length == 0) {
                return fixed;
            }
            var ret = "";
            var match = value.match(/\n+$/g);
            var newlines = match ? match[0].split("\n").length : 0;
            if (newlines <= 2) {
                var repeats = newlines == 0 ? 2 : 1;
                ret += newline.repeat(repeats);
            }
            return ret + fixed;
        },
        insertTrigger: function () {
            var vendor = this.vendors[this.settings.vendor];
            var sibling = !this.settings.sibling
                ? 0
                : document.querySelectorAll(
                    this.settings.sibling + ":not([" + this.ns.dataPlugin + "])"
                )[0];
            var areas;
            if (this.settings.mode == "auto") {
                areas = this.vendors[
                    vendor.hasOwnProperty("getEditor") ? this.settings.vendor : "default"
                ].getEditor();
            } else {
                var targets = document.querySelectorAll(
                    "[" +
                    this.ns.dataPluginTrigger +
                    "][data-target]:not([" +
                    this.ns.dataPluginId +
                    "])"
                );
                var targetsSel = [];
                for (var i = 0; i < targets.length; i++) {
                    targetsSel.push(targets[i].dataset.target);
                }
                if (targetsSel.length > 0) {
                    areas = document.querySelectorAll(targetsSel.join(","));
                }
            }
            if (!areas) {
                return;
            }
            if (!document.getElementById(this.ns.pluginStyle) && this.settings.css) {
                var style = document.createElement("style");
                var rules = this.settings.css;
                rules = this.appyTemplate(rules);
                style.type = "text/css";
                style.innerHTML = rules.replace(/%p/g, "." + this.ns.plugin);
                style.setAttribute("id", this.ns.pluginStyle);
                document.body.appendChild(style);
            }
            if (!(areas instanceof NodeList)) {
                areas = [areas];
            }

            var count = 0;
            for (var i = 0; i < areas.length; i++) {
                if (areas[i].getAttribute(this.ns.dataPluginTarget)) {
                    continue;
                }
                var target = sibling ? sibling : areas[i];
                target.setAttribute(this.ns.dataPlugin, "sibling");
                target.insertAdjacentHTML(
                    { before: "beforebegin", after: "afterend" }[
                    this.settings.siblingPos
                    ],
                    this.appyTemplate(this.settings.html)
                );
                var trigger = target.parentElement.querySelector(
                    "[" + this.ns.dataPluginTrigger + "]"
                );
                this.setBoundId(trigger, areas[i]);
                count++;
            }
            this.triggerCounter = count;
            if (typeof vendor.callback == "function") {
                vendor.callback.call();
            }
        },
        appyTemplate: function (template) {
            if (!this.cacheTable) {
                var table = [
                    { "%iconSvg": this.iconSvg },
                    { "%text": this.settings.langString }
                ];
                if (this.palette) {
                    var re = /%(\d+)/g;
                    var match = re.exec(template);
                    var arr = [];
                    while (match !== null) {
                        if (arr.indexOf(match[1]) == -1) {
                            arr.push(match[1]);
                        }
                        match = re.exec(template);
                    }
                    if (arr) {
                        arr.sort(function (a, b) {
                            return b - a;
                        });
                        var vendor = this.vendors[this.settings.vendor];
                        for (var i = 0; i < arr.length; i++) {
                            var index = arr[i] - 1;
                            var value = this.palette[index] || "";
                            if (
                                !value &&
                                this.settings.vendor !== "default" &&
                                this.settings.palette !== "default"
                            ) {
                                value = this.palette[index - 2];
                            }
                            var o = {};
                            o["%" + arr[i]] = value;
                            table.push(o);
                        }
                    }
                }
                var bClass = this.settings.buttonClass || this.ns.plugin + "-button";
                var named = [
                    {
                        "%cClass":
                            this.settings.containerClass || this.ns.plugin + "-container"
                    },
                    { "%bClass": bClass },
                    { "%iClass": bClass + "-icon" },
                    { "%tClass": bClass + "-text" },
                    { "%x": this.ns.dataPluginTrigger },
                    { "%p": this.ns.plugin }
                ];
                for (var i = 0; i < named.length; i++) {
                    table.push(named[i]);
                }
                this.cacheTable = table;
            }
            return this.strtr(template, this.cacheTable);
        },
        strtr: function (str, replaces) {
            var str = str.toString();
            if (!str || typeof replaces == typeof undefined) {
                return str;
            }
            for (var i = 0; i < replaces.length; i++) {
                var obj = replaces[i];
                for (var key in obj) {
                    if (typeof obj[key] !== typeof undefined) {
                        re = new RegExp(key, "g");
                        str = str.replace(re, obj[key]);
                    }
                }
            }
            return str;
        },
        setBoundId: function (trigger, target) {
            var id = this.generateGuid();
            trigger.setAttribute(this.ns.dataPluginId, id);
            // target.setAttribute(this.ns.dataPluginId, id);
            target.setAttribute(this.ns.dataPluginTarget, id);
        },
        openPopup: function (id) {
            if (typeof id !== "string") {
                return;
            }
            var self = this;
            if (typeof this.popups == typeof undefined) {
                this.popups = {};
            }
            if (typeof this.popups[id] !== typeof undefined) {
                this.popups[id].window.focus();
                return;
            } else {
                this.popups[id] = {};
            }
            var client = {
                l: window.screenLeft != undefined ? window.screenLeft : screen.left,
                t: window.screenTop != undefined ? window.screenTop : screen.top,
                w: window.innerWidth
                    ? window.innerWidth
                    : document.documentElement.clientWidth
                        ? document.documentElement.clientWidth
                        : screen.width,
                h: window.innerHeight
                    ? window.innerHeight
                    : document.documentElement.clientHeight
                        ? document.documentElement.clientHeight
                        : screen.height
            };
            var size = {
                w: 720,
                h: 690
            };
            var tolerance = {
                w: 0.5,
                h: 0.85
            };
            for (var key in size) {
                if (size[key] / client[key] > tolerance[key]) {
                    size[key] = client[key] * tolerance[key];
                }
            }
            var pos = {
                l: Math.trunc(client.w / 2 - size.w / 2 + client.l),
                t: Math.trunc(client.h / 2 - size.h / 2 + client.t)
            };
            this.popups[id].window = window.open(
                this.settings.url,
                id,
                "width=" +
                size.w +
                ",height=" +
                size.h +
                ",top=" +
                pos.t +
                ",left=" +
                pos.l
            );
            this.popups[id].timer = window.setInterval(function () {
                if (
                    !self.popups[id].window ||
                    self.popups[id].window.closed !== false
                ) {
                    window.clearInterval(self.popups[id].timer);
                    self.popups[id] = undefined;
                }
            }, 200);
        },
        postSettings: function (id) {
            this.popups[id].window.postMessage(
                { id: id, settings: this.settings },
                this.settings.url
            );
        },
        liveBind: function (qs, et, c) {
            document.addEventListener(
                et,
                function (e) {
                    var caller = document.querySelectorAll(qs);
                    if (!caller) {
                        return;
                    }
                    var el = e.target;
                    var index = -1;
                    while (
                        el &&
                        (index = Array.prototype.indexOf.call(caller, el)) === -1
                    ) {
                        el = el.parentElement;
                    }
                    if (index > -1) {
                        e.preventDefault();
                        c.call(e, el);
                    }
                },
                true
            );
        },
        prepare: function () {
            var self = this;
            this.ns.dataPlugin = "data-" + this.ns.plugin;
            this.ns.dataPluginId = this.ns.dataPlugin + "-id";
            this.ns.dataPluginTrigger = this.ns.dataPlugin + "-trigger";
            this.ns.dataPluginTarget = this.ns.dataPlugin + "-target";
            this.ns.pluginStyle = this.ns.plugin + "-style";
            this.ns.selDataPluginTrigger = "[" + this.ns.dataPluginTrigger + "]";
            var srcEl =
                document.currentScript ||
                document.getElementById(this.ns.plugin + "-src");
            if (!srcEl) {
                srcEl = { dataset: {} };
            } else if (srcEl.dataset["buttonTemplate"]) {
                srcEl.dataset["html"] = srcEl.dataset["buttonTemplate"];
            }
            var nocss = 0;
            this.settings = {};
            for (var key in this.defaultSettings) {
                var value =
                    srcEl && srcEl.dataset[key]
                        ? srcEl.dataset[key]
                        : this.defaultSettings[key];
                if (value === "1" || value === "0") {
                    value = value == "true";
                }
                if (
                    typeof value == "string" &&
                    this.classProps.indexOf(key.replace(/Class$/, "")) > -1
                ) {
                    nocss = 1;
                }
                this.settings[key] = value;
            }
            if (this.settings.vendor == "auto") {
                this.settings.vendor = "default";
                this.settings.fitEditor = 0;
                for (var key in this.vendors) {
                    if (key == "default") continue;
                    if (typeof window[this.vendors[key].check] !== typeof undefined) {
                        this.settings.vendor = key;
                        break;
                    }
                }
            }
            var skip = ["lang", "url", "vendor", "target"];

            if (this.settings.vendor == "default") {
                this.vendors.default.settings = {};
            }

            var vendor = this.vendors[this.settings.vendor];
            if (vendor.settings) {
                for (var key in vendor.settings) {
                    if (!srcEl || !srcEl.dataset.hasOwnProperty(key)) {
                        this.settings[key] = vendor.settings[key];
                    }
                }
            } else {
                vendor.settings = {};
                for (var key in this.defaultSettings) {
                    if (skip.indexOf(key) == -1) {
                        vendor.settings[key] = this.defaultSettings[key];
                    }
                }
            }

            if (this.settings.vendor !== "default") {
                if (
                    !vendor.settings.hasOwnProperty("fitEditor") &&
                    !srcEl.dataset.hasOwnProperty("fitEditor")
                ) {
                    this.settings.fitEditor = 1;
                }
                if (this.settings.fitEditor) {
                    nocss = !vendor.settings.css;
                } else {
                    var skip = ["autoInsert", "observe", "observeCache"];
                    for (var key in vendor.settings) {
                        if (skip.indexOf(key) == -1 && !srcEl.dataset.hasOwnProperty(key)) {
                            this.settings[key] = this.defaultSettings[key];
                        }
                    }
                }
            }
            if (nocss) {
                this.settings.css = "";
            } else {
                this.settings.css = this.settings.css.replace(
                    "%defaultCSS",
                    this.defaultSettings.css
                );
                if (vendor.settings.extracss && this.settings.css) {
                    this.settings.css += vendor.settings.extracss;
                }
                var palette = this.settings.palette.split(",");
                if (palette.length > 1) {
                    this.palette = palette;
                } else if (!this.palettes.hasOwnProperty(palette)) {
                    this.settings.palette = "default";
                }
                if (!this.palette) {
                    this.palette = (this.settings.fitEditor &&
                        vendor.palettes &&
                        vendor.palettes[this.settings.palette]
                        ? vendor
                        : this
                    ).palettes[this.settings.palette];
                }
            }
            var props = this.classProps;
            for (var i = 0; i < props.length; i++) {
                var prop = props[i] + "Class";
                if (typeof this.settings[prop] !== "string") {
                    this.settings[prop] = this.ns.plugin + "-" + props[i];
                    if (this.settings.fitEditor) {
                        this.settings[prop] += "--" + this.settings.vendor;
                    }
                }
            }
            var clientLang = (this.settings.lang == "auto"
                ? navigator.language || navigator.userLanguage
                : this.settings.lang
            ).replace("-", "_");
            this.settings.langString = "Upload images";
            var langKey =
                clientLang in this.l10n
                    ? clientLang
                    : clientLang.substring(0, 2) in this.l10n
                        ? clientLang.substring(0, 2)
                        : null;
            if (langKey) {
                this.settings.langString = this.l10n[langKey];
            }
            var parser = document.createElement("a");
            parser.href = this.settings.url;
            this.originUrlPattern =
                "^" +
                (parser.protocol + "//" + parser.hostname)
                    .replace(/\./g, "\\.")
                    .replace(/\//g, "\\/") +
                "$";
            var namedTargets = document.querySelectorAll(
                this.ns.selDataPluginTrigger + "[data-target]"
            );
            if (namedTargets.length > 0) {
                for (var i = 0; i < namedTargets.length; i++) {
                    var target = document.querySelector(namedTargets[i].dataset.target);
                    this.setBoundId(namedTargets[i], target);
                }
            }
            if (this.settings.observe) {
                var observe = this.settings.observe;
                if (this.settings.observeCache) {
                    observe += ":not([" + this.ns.dataPlugin + "])";
                }
                this.liveBind(
                    observe,
                    "click",
                    function (el) {
                        el.setAttribute(self.ns.dataPlugin, 1);
                        self.observe();
                    }.bind(this)
                );
            }
            if (this.settings.sibling && !this.settings.onDemand) {
                this.waitForSibling();
            } else {
                if (this.settings.init == "onload") {
                    if (document.readyState === "loading") {
                        document.addEventListener(
                            "DOMContentLoaded",
                            function (event) {
                                self.init();
                            },
                            false
                        );
                    } else {
                        this.init();
                    }
                } else {
                    this.observe();
                }
            }
        },
        observe: function () {
            this.waitForSibling("observe");
        },
        waitForSibling: function (m) {
            var fn = this.initialized ? "insertTrigger" : "init";
            if (this.settings.sibling) {
                var sibling = document.querySelector(
                    this.settings.sibling + ":not([" + this.ns.dataPlugin + "])"
                );
            } else if (m == "observe") {
                this[fn]();
                if (this.triggerCounter) {
                    return;
                }
            }
            if (!sibling) {
                if (document.readyState === "complete" && m !== "observe") {
                    return;
                }
                setTimeout(
                    (m == "observe" ? this.observe : this.waitForSibling).bind(this),
                    250
                );
            } else {
                this[fn]();
            }
        },
        init: function () {
            this.insertTrigger();
            var self = this;
            var vendor = this.vendors[this.settings.vendor];
            this.liveBind(this.ns.selDataPluginTrigger, "click", function (el) {
                var id = el.getAttribute(self.ns.dataPluginId);
                self.openPopup(id);
            });
            window.addEventListener(
                "message",
                function (e) {
                    var regex = new RegExp(self.originUrlPattern, "i");
                    if (
                        !regex.test(e.origin) &&
                        (typeof e.data.id == typeof undefined ||
                            typeof e.data.message == typeof undefined)
                    ) {
                        return;
                    }
                    var id = e.data.id;
                    if (!id || e.source !== self.popups[id].window) {
                        return;
                    }
                    if (
                        e.data.requestAction &&
                        self.hasOwnProperty(e.data.requestAction)
                    ) {
                        self[e.data.requestAction](id);
                        return;
                    }
                    var area;
                    if (self.settings.vendor !== "default") {
                        if (
                            vendor.hasOwnProperty("useCustomEditor") &&
                            vendor.useCustomEditor()
                        ) {
                            vendor.editorValue(e.data.message, id);
                            return;
                        } else if (vendor.hasOwnProperty("getEditor")) {
                            area = vendor.getEditor();
                        }
                    }
                    if (!area) {
                        area = document.querySelector(
                            "[" + self.ns.dataPluginTarget + '="' + id + '"]'
                        );
                        if (!area) {
                            alert("Target not found"); // calma calma que no panda el cÃºnico
                            return;
                        }
                    }
                    var valueProp =
                        area.getAttribute("contenteditable") === null
                            ? "value"
                            : "innerHTML";
                    area[valueProp] += self.getNewValue(area, e.data.message);
                    var events = ["blur", "focus", "input", "change", "paste"];
                    for (var i = 0; i < events.length; i++) {
                        var event = new Event(events[i]);
                        area.dispatchEvent(event);
                    }
                },
                false
            );
            this.initialized = 1;
        }
    };
    var redactor2 = ["WoltLab", "XF1"];
    for (var i = 0; i < redactor2.length; i++) {
        PUP.vendors[redactor2[i]] = Object.assign(
            Object.assign({}, PUP.vendors.redactor2),
            PUP.vendors[redactor2[i]]
        );
    }
    PUP.prepare();
})();