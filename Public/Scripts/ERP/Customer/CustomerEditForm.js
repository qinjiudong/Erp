// 客户资料 - 新增或编辑界面
Ext.define("ERP.Customer.CustomerEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        me.adding = entity == null;
        var buttons = [];
        if (!entity) {
            buttons.push({
                text: "保存并继续新增",
                formBind: true,
                handler: function () {
                    var me = this;
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
            }, scope: me
        }, {
            text: me.adding ? "关闭" : "取消", handler: function () {
                me.close();
            }, scope: me
        });

        var categoryStore = me.getParentForm().categoryGrid.getStore();

        Ext.apply(me, {
            title: entity == null ? "新增客户" : "编辑客户",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 550,
            height: 310,
            layout: "fit",
            items: [
                {
                    id: "editForm",
                    xtype: "form",
                    layout : {
    					type : "table",
    					columns : 2
    				},
                    height: "100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 90,
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
                            id: "editCategory",
                            xtype: "combo",
                            fieldLabel: "分类",
                            allowBlank: false,
                            blankText: "没有输入客户分类",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: categoryStore,
                            queryMode: "local",
                            editable: false,
                            value: categoryStore.getAt(0).get("id"),
                            name: "categoryId",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editCode",
                            fieldLabel: "编码",
                            allowBlank: false,
                            blankText: "没有输入客户编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity == null ? null : entity.get("code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editName",
                            fieldLabel: "客户名称",
                            allowBlank: false,
                            blankText: "没有输入客户名称",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity == null ? null : entity.get("name"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            },
                            width: 490,
                            colspan: 2
                        },{
                            id: "editAddress",
                            fieldLabel: "地址",
                            name: "address",
                            value: entity == null ? null : entity.get("address"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            },
                            width: 490,
                            colspan: 2
                        },{
                            id: "editContact01",
                            fieldLabel: "联系人",
                            name: "contact01",
                            value: entity == null ? null : entity.get("contact01"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editMobile01",
                            fieldLabel: "手机",
                            name: "mobile01",
                            value: entity == null ? null : entity.get("mobile01"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editTel01",
                            fieldLabel: "固话",
                            name: "tel01",
                            value: entity == null ? null : entity.get("tel01"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editQQ01",
                            fieldLabel: "QQ",
                            name: "qq01",
                            value: entity == null ? null : entity.get("qq01"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editContact02",
                            fieldLabel: "备用联系人",
                            name: "contact02",
                            value: entity == null ? null : entity.get("contact02"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editMobile02",
                            fieldLabel: "备用联系人手机",
                            name: "mobile02",
                            value: entity == null ? null : entity.get("mobile02"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editTel02",
                            fieldLabel: "备用联系人固话",
                            name: "tel02",
                            value: entity == null ? null : entity.get("tel02"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editQQ02",
                            fieldLabel: "备用联系人QQ",
                            name: "qq02",
                            value: entity == null ? null : entity.get("qq02"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },{
                            id: "editAddressReceipt",
                            fieldLabel: "收货地址",
                            name: "addressReceipt",
                            value: entity == null ? null : entity.get("addressReceipt"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            },
                            width: 490,
                            colspan: 2
                        },{
                            id: "editInitReceivables",
                            fieldLabel: "应收期初余额",
                            name: "initReceivables",
                            xtype: "numberfield", 
                            hideTrigger: true,
                            value: entity == null ? null : entity.get("initReceivables"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        }, {
                            id: "editInitReceivablesDT",
                            fieldLabel: "余额日期",
                            name: "initReceivablesDT",
                            xtype: "datefield",
                            format: "Y-m-d",
                            value: entity == null ? null : entity.get("initReceivablesDT"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditLastSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ],
                    buttons: buttons
                }],
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

        me.__editorList = ["editCategory", "editCode", "editName", "editAddress", "editContact01",
            "editMobile01", "editTel01", "editQQ01", "editContact02",
            "editMobile02", "editTel02", "editQQ02", "editAddressReceipt", "editInitReceivables", "editInitReceivablesDT"];

    },
    
    onWndShow: function () {
    	var me = this;
        if (!me.adding) {
        	// 编辑客户资料
            var el = me.getEl();
            el.mask(ERP.Const.LOADING);
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Customer/customerInfo",
                params: {
                	id: me.getEntity().get("id")
                },
                method: "POST",
                callback: function (options, success, response) {
                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        Ext.getCmp("editCategory").setValue(data.categoryId);
                        Ext.getCmp("editCode").setValue(data.code);
                        Ext.getCmp("editName").setValue(data.name);
                        Ext.getCmp("editAddress").setValue(data.address);
                        Ext.getCmp("editContact01").setValue(data.contact01);
                        Ext.getCmp("editMobile01").setValue(data.mobile01);
                        Ext.getCmp("editTel01").setValue(data.tel01);
                        Ext.getCmp("editQQ01").setValue(data.qq01);
                        Ext.getCmp("editContact02").setValue(data.contact02);
                        Ext.getCmp("editMobile02").setValue(data.mobile02);
                        Ext.getCmp("editTel02").setValue(data.tel02);
                        Ext.getCmp("editQQ02").setValue(data.qq02);
                        Ext.getCmp("editAddressReceipt").setValue(data.addressReceipt);
                        Ext.getCmp("editInitReceivables").setValue(data.initReceivables);
                        Ext.getCmp("editInitReceivablesDT").setValue(data.initReceivablesDT);
                    }

                    el.unmask();
                }
            });
        } else {
        	// 新建客户资料
            var grid = me.getParentForm().categoryGrid;
            var item = grid.getSelectionModel().getSelection();
            if (item == null || item.length != 1) {
                return;
            }

            Ext.getCmp("editCategory").setValue(item[0].get("id"));
        }

        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());
    },
    
    onWndClose: function () {
        var me = this;
        if (me.__lastId) {
            me.getParentForm().freshCustomerGrid(me.__lastId);
        }
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Customer/editCustomer",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                me.__lastId = action.result.id;
                ERP.MsgBox.tip("数据保存成功");
                me.focus();
                if (thenAdd) {
                    me.clearEdit();
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
    onEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
            var me = this;
            var id = field.getId();
            for (var i = 0; i < me.__editorList.length; i++) {
                var editorId = me.__editorList[i];
                if (id === editorId) {
                    var edit = Ext.getCmp(me.__editorList[i + 1]);
                    edit.focus();
                    edit.setValue(edit.getValue());
                }
            }
        }
    },
    onEditLastSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                this.onOK(this.adding);
            }
        }
    },
    clearEdit: function () {
        Ext.getCmp("editCode").focus();

        var editors = ["editCode", "editName", "editAddress", "editContact01",
            "editMobile01", "editTel01", "editQQ01", "editContact02",
            "editMobile02", "editTel02", "editQQ02", "editAddressReceipt", "editInitReceivables", "editInitReceivablesDT"];
        for (var i = 0; i < editors.length; i++) {
            var edit = Ext.getCmp(editors[i]);
            if (edit) {
                edit.setValue(null);
                edit.clearInvalid();
            }
        }
    }
});