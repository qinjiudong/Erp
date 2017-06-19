// 新增或编辑店铺
Ext.define("ERP.Shop.ShopEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    getBaseURL: function () {
        return ERP.Const.BASE_URL;
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        Ext.apply(me, {
            title: entity === null ? "新增店铺" : "编辑店铺",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 300,
            layout: "fit",
            defaultFocus: "editName",
            items: [
                {
                    id: "editForm",
                    xtype: "form",
                    layout: "form",
                    height: "100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 50,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "id",
                            value: entity === null ? null : entity.get("id")
                        },
//                        {
//                            id: "editParentOrg",
//                            xtype: "ERP_position_editor",
//                            parentItem: me,
//                            fieldLabel: "上级店铺",
//                            name:"fullname",
//                            listeners: {
//                                specialkey: {
//                                    fn: me.onEditParentOrgSpecialKey,
//                                    scope: me
//                                }
//                            }
//                        },
                        {
                            id: "editName",
                            fieldLabel: "名称",
                            allowBlank: false,
                            blankText: "没有输入名称",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity === null ? null : entity.get("text"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editAddress",
                            fieldLabel: "地址",
                            allowBlank: false,
                            blankText: "没有输入地址",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "address",
                            value: entity === null ? null : entity.get("address"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditAddressSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editCode",
                            fieldLabel: "编码",
                            allowBlank: false,
                            blankText: "没有输入编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity === null ? null : entity.get("Code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
                                    scope: me
                                }
                            }
                        },
//                        {
//                            id: "editShopKeeper",
//                            fieldLabel: "负责人",
//                            allowBlank: false,
//                            blankText: "没有输入负责人",
//                            beforeLabelTextTpl: ERP.Const.REQUIRED,
//                            name: "shopkeeper",
//                            value: entity === null ? null : entity.get("ShopKeeper")
//                        },

                        {
                            xtype: "hidden",
                            name: "shopkeeperId",
                            id: "shopkeeperIds",
                        }, 

                        {
                            id: "editShopKeeper",
                            xtype: "jyerp_user_field",
                            parentCmp: me,
                            fieldLabel: "负责人",
                            name:"shopkeeper",
                        },
                        {
                            id: "editRemark",
                            fieldLabel: "备注",
                            name: "remark",
                            value: entity === null ? null : entity.get("Remark")
                        },
                        {
                            id: "editSort",
                            fieldLabel: "排序",
                            allowBlank: true,
                            name: "sort",
                            value: entity === null ? 10 : entity.get("Sort")
                        }
                    ],
                    buttons: [
                        {
                            text: "确定",
                            formBind: true,
                            iconCls: "ERP-button-ok",
                            handler: me.onOK,
                            scope: me
                        },
                        {
                            text: "取消", handler: function () {
                                ERP.MsgBox.confirm("请确认是否取消操作?", function () {
                                    me.close();
                                });
                            }, scope: me
                        }
                    ]
                }
            ],
            listeners: {
                show: {
                    fn: me.onEditFormShow,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },
    onEditFormShow: function () {
        var me = this;

        var entity = this.getEntity();
        if (entity === null) {
            return;
        }
        var form = this;
        form.getEl().mask("数据加载中...");
        Ext.Ajax.request({
            url: me.getBaseURL() + "Home/Shop/shopinfos",
            method: "POST",
            params: {id: entity.get("id")},
            callback: function (options, success, response) {
                form.getEl().unmask();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    Ext.getCmp("editName").setValue(data.name);
                    Ext.getCmp("editCode").setValue(data.code);
                    Ext.getCmp("editSort").setValue(data.sort);
                    Ext.getCmp("editAddress").setValue(data.address);
                    Ext.getCmp("editShopKeeper").setValue(data.shopkeeper);
                    Ext.getCmp("editRemark").setValue(data.remark);
                }
            }
        });
    },
    setParentOrg: function (data) {
    		//if(data.pid == -1){alert('店铺选择错误');return;}
        Ext.getCmp("editParentOrg").setValue(data.text);
        Ext.getCmp("editWherehouseId").setValue(data.wherehouse_id);
        Ext.getCmp("editPid").setValue(data.pid);
    },
    // private
    onOK: function () {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask("数据保存中...");
        f.submit({
            url: me.getBaseURL() + "Home/Shop/editShop",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                me.close();
                me.getParentForm().freshCategoryGrid();
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    Ext.getCmp("editName").focus();
                });
            }
        });
    },
    onEditNameSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editParentOrg").focus();
        }
    },
    onEditAddressSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editParentOrg").focus();
        }
    },
    onEditParentOrgSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editCode").focus();
        }
    },
});