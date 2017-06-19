// 商品分类 - 编辑界面
Ext.define("ERP.Shop.CategoryEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();

        me.__lastId = entity == null ? null : entity.get("id");

        me.adding = entity == null;

        var buttons = [];
//        if (!entity) {
//            buttons.push({
//                text: "保存并继续调拨",
//                formBind: true,
//                handler: function () {
//                    me.onOK(true);
//                },
//                scope: me
//            });
//        }

        buttons.push({
            text: "保存",
            formBind: true,
            iconCls: "ERP-button-ok",
            handler: function () {
                me.onOK(false);
            }, scope: me
        }, {
            text: entity == null ? "关闭" : "取消", handler: function () {
                me.close();
            }, scope: me
        });

        Ext.apply(me, {
            title: "商品调拨",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 180,
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
                            name: "id"
                        }, 

                        {
                            xtype: "hidden",
                            name: "outShopId",
                            id: "outShopIds",
                        }, 

                        {
                            id: "editOutShopName",
                            xtype: "jyerp_goods_category_field",
                            parentCmp: me,
                            fieldLabel: "调出店铺",
                            name:"outShopName",
                        },

                        {
                            xtype: "hidden",
                            name: "inShopId",
                            id: "inShopIds",
                        }, 
                        {
                            id: "editInShopName",
                            xtype: "jyerp_goods_category_fields",
                            parentCmp: me,
                            fieldLabel: "调入店铺",
                            name:"inShopName",
                        },

                        {
                            id: "editCode",
                            fieldLabel: "商品条码",
                            allowBlank: false,
                            blankText: "没有输入商品条码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editNum",
                            fieldLabel: "调拨数量",
                            allowBlank: false,
                            blankText: "没有输入调拨数量",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "number",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ],
                    buttons: buttons
                }],
            listeners: {
                close: {
                    fn: me.onWndClose,
                    scope: me
                },
                show: {
                    fn: me.onWndShow,
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
            url: ERP.Const.BASE_URL + "Home/Shop/goodsTransfer",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                ERP.MsgBox.tip("调拨成功");
                me.focus();
                me.__lastId = action.result.id;
                me.close();
                me.getParentForm().freshCategoryGrid(me.__lastId);
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
            Ext.getCmp("editName").focus();
        }
    },
    onEditNameSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                Ext.getCmp("editCode").focus();
                var me = this;
                me.onOK(me.adding);
            }
        }
    },
    onWndClose: function () {
        if (this.__lastId) {
            //this.getParentForm().freshCategoryGrid(this.__lastId);
        }
    },
    onWndShow: function() {
        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());
    },
    setParentShopCategory: function(data,type){
        if(type=='out') {
            Ext.getCmp("editOutShopName").setValue(data.text);
            Ext.getCmp("outShopIds").setValue(data.id);
        }
        else if(type=='in') {
            Ext.getCmp("editInShopName").setValue(data.text);
            Ext.getCmp("inShopIds").setValue(data.id);
        }
    }
});