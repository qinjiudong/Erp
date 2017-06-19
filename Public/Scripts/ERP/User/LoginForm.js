// 登录界面
Ext.define("ERP.User.LoginForm", {
    extend: 'Ext.window.Window',
    
    config: {
        demoInfo: ""
    },

    header: {
        title: "<span style='font-size:120%'>登录 - 生鲜电商ERP</span>",
        iconCls: "ERP-login",
        height: 40
    },
    modal: true,
    closable: false,
    onEsc: Ext.emptyFn,
    width: 400,
    layout: "fit",
    defaultFocus: Ext.util.Cookies.get("ERP_user_login_name") ? "editPassword" : "editLoginName",

    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            height: me.getDemoInfo() == "" ? 140 : 200,
            items: [{
                id: "loginForm",
                xtype: "form",
                layout: "form",
                height: "100%",
                border: 0,
                bodyPadding: 5,
                defaultType: 'textfield',
                fieldDefaults: {
                    labelWidth: 60,
                    labelAlign: "right",
                    labelSeparator: "",
                    msgTarget: 'side'
                },
                items: [{
                    id: "editLoginName",
                    fieldLabel: "登录名",
                    allowBlank: false,
                    blankText: "没有输入登录名",
                    beforeLabelTextTpl: ERP.Const.REQUIRED,
                    name: "loginName",
                    value: Ext.util.Cookies.get("ERP_user_login_name"),
                    listeners: {
                        specialkey: function (field, e) {
                            if (e.getKey() === e.ENTER) {
                                Ext.getCmp("editPassword").focus();
                            }
                        }
                    }
                }, {
                    id: "editPassword",
                    fieldLabel: "密码",
                    allowBlank: false,
                    blankText: "没有输入密码",
                    beforeLabelTextTpl: ERP.Const.REQUIRED,
                    inputType: "password",
                    name: "password",
                    listeners: {
                        specialkey: function (field, e) {
                            if (e.getKey() === e.ENTER) {
                                if (Ext.getCmp("loginForm").getForm().isValid()) {
                                    me.onOK();
                                }
                            }
                        }
                    }
                },{
                    xtype: "displayfield",
                    value: me.getDemoInfo()
                }],
                buttons: [{
                    text: "登录",
                    formBind: true,
                    handler: me.onOK,
                    scope: me,
                    iconCls: "ERP-button-ok"
                },{
                    text: "帮助",
                    iconCls: "ERP-help",
                    handler: function() {
                        alert("如需帮助，请联系管理员。");
                    }
                }]
            }]
        });

        me.callParent(arguments);
    },

    // private
    onOK: function () {
        var me = this;

        var loginName = Ext.getCmp("editLoginName").getValue();
        var f = Ext.getCmp("loginForm");
        var el = f.getEl() || Ext.getBody();
        el.mask("系统登录中...");
        f.getForm().submit({
            url: ERP.Const.BASE_URL + "Home/User/loginPOST",
            method: "POST",
            success: function (form, action) {
                Ext.util.Cookies.set("ERP_user_login_name", encodeURIComponent(loginName),
                    Ext.Date.add(new Date(), Ext.Date.YEAR, 1));

                location.replace(ERP.Const.BASE_URL);
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    var editPassword = Ext.getCmp("editPassword");
                    editPassword.setValue(null);
                    editPassword.clearInvalid();
                    editPassword.focus();
                });
            }
        });
    }
});