Ext.define("ERP.Position.GoodsEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
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
            title: entity == null ? "新增商品" : "编辑商品",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 130,
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
                        labelWidth: 60,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "id",
                            value: entity === null ? null : entity.id
                        },
                        {
                            id: "editPositionId",
                            xtype: "hidden",
                            name: "position_id",
                            value: entity === null ? null : entity.position_id
                        },
                        {
                            id: "editParentOrg",
                            xtype: "ERP_position_editor",
                            parentItem: me,
                            allowBlank: false,
                            blankText: "请选择仓位",
                            fieldLabel: "选择仓位",
                            name:"fullname",
                            value: entity === null ? null : entity.position_name,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditParentOrgSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editCode",
                            fieldLabel: "选择商品",
                            allowBlank: false,
                            blankText: "请选择商品",
                            
                            xtype: "jyerp_goodsfield",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity == null ? null : entity.code,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
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
    setParentOrg: function (data) {
    		if(data.pid == 0){alert('请选择正确仓位');return;}
        Ext.getCmp("editParentOrg").setValue(data.text);
        Ext.getCmp("editPositionId").setValue(data.id);
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Position/editgoods",
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
       this.getParentForm().freshOrgGrid();
    },
    onWndShow: function () {
        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());
    }
});