Ext.define("ERP.Site.CategoryEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        me.adding = entity == null;
        me.__lastId = entity == null ? null : entity.get("id");

        var buttons = [];
        if (!entity) {
            buttons.push({
                text: "保存并继续新增",
                formBind: true,
                handler: function () {
                    me.onOK(true);
                },
                scope: this
            });
        }

        buttons.push({
            text: "保存",
            formBind: true,
            iconCls: "ERP-button-ok",
            handler: function () {
                me.onOK(false);
            }, scope: this
        }, {
            text: entity == null ? "关闭" : "取消", handler: function () {
                me.close();
            }, scope: me
        });

        Ext.apply(me, {
            title: entity == null ? "新增送货区域" : "编辑送货区域",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 160,
            layout: "fit",
            items: [
                {
                    id: "editForm",
                    xtype: "form",
                    layout: "form",
                    height: "100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 100,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        }, {
                            id: "editCode",
                            fieldLabel: "送货区域编码",
                            allowBlank: false,
                            blankText: "没有输入送货区域编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity == null ? null : entity.get("code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editName",
                            fieldLabel: "送货区域名称",
                            allowBlank: false,
                            blankText: "没有输入送货区域名称",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity == null ? null : entity.get("name"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editDes",
                            fieldLabel: "送货区域描述",
                            allowBlank: true,
                            name: "des",
                            value: entity == null ? null : entity.get("des"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ],
                    buttons: buttons,
                }
            ],
            listeners: {
                show: {
                    fn: me.onWndShow,
                    scope: me
                },
                close: {
                    fn: me.onWndClose,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Site/editCategory",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                ERP.MsgBox.tip("数据保存成功");
                me.focus();
                me.__lastId = action.result.id;
                if (thenAdd) {
                    var editCode = Ext.getCmp("editCode");
                    editCode.setValue(null);
                    editCode.clearInvalid();
                    editCode.focus();

                    var editName = Ext.getCmp("editName");
                    editName.setValue(null);
                    editName.clearInvalid();
                } else {
                    me.close();
                }
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    Ext.getCmp("editCode").focus();
                });
            }
        });
    },
    onEditCodeSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var editName = Ext.getCmp("editName");
            editName.focus();
            editName.setValue(editName.getValue());
        }
    },
    onEditNameSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                var me = this;
                Ext.getCmp("editCode").focus();
                me.onOK(me.adding);
            }
        }
    },
    onWndClose: function () {
        var me = this;
        if (me.__lastId) {
            me.getParentForm().freshCategoryGrid(me.__lastId);
        }
    },
    onWndShow: function () {
        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());
    }
});