// 站点档案 - 新建或编辑界面
Ext.define("ERP.Site.SiteEditForm", {
    extend: "Ext.window.Window",
    
    config: {
        parentForm: null,
        entity: null
    },
    
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        this.adding = entity == null;

        var buttons = [];
        if (!entity) {
            buttons.push({
                text: "保存并继续新增",
                formBind: true,
                handler: function () {
                    me.onOK(true);
                },
                scope: me
            });
        }

        //站点的配送属性
        var attr = Ext.regModel('ERPAttr', {
            fields: ["id", "name"]
        });
        var attrStore = Ext.create('Ext.data.Store', {
            model: 'ERPAttr',
            data: [{"id":"0","name":"自提站点"},{"id":"1", "name":"送货站点"}]
        });
        me.attrStore = attrStore;

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

        var categoryStore = me.getParentForm().categoryGrid.getStore();
        var lineStore = me.getParentForm().lineGrid.getStore();

        Ext.apply(me, {
            title: entity == null ? "新增站点" : "编辑站点",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 550,
            height: 260,
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
                        },
                        {
                            id: "editCategory",
                            xtype: "combo",
                            fieldLabel: "区域",
                            allowBlank: false,
                            blankText: "没有输入站点送货区域",
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
                        },
                        {
                            id: "editCode",
                            fieldLabel: "编码",
                            allowBlank: false,
                            blankText: "没有输入站点编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity == null ? null : entity.get("code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editName",
                            fieldLabel: "站点地址",
                            allowBlank: false,
                            blankText: "没有输入站点",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity == null ? null : entity.get("name"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            },
                            colspan: 2,
                            width: 490
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
                            colspan: 2,
                            width: 490
                        },
                        {
                            id: "editContact01",
                            fieldLabel: "联系人(站长)",
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
                            id: "editAttr",
                            xtype: "combo",
                            fieldLabel: "站点属性",
                            allowBlank: false,
                            blankText: "没有选择站点属性",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: attrStore,
                            queryMode: "local",
                            editable: false,
                            value: "0",
                            name: "attr",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editFreight",
                            fieldLabel: "运费",
                            name: "freight",
                            value: entity == null ? 0 : entity.get("freight"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editHouses",
                            fieldLabel: "站点总户数",
                            name: "houses",
                            value: entity == null ? 0 : entity.get("houses"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editLine",
                            xtype: "combo",
                            fieldLabel: "路线",
                            allowBlank: false,
                            blankText: "没有输入路线",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: lineStore,
                            queryMode: "local",
                            editable: false,
                            value: lineStore.getAt(0).get("id"),
                            name: "lineId",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editSort",
                            fieldLabel: "排序",
                            name: "sort",
                            value: entity == null ? 0 : entity.get("sort"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
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

        me.__editorList = ["editCategory", "editCode", "editName", "editAddress", "editContact01", "editAttr", "editMobile01", "editFreight","editHouses"];
    },
    onWndShow: function () {
        var me = this;
        if (me.adding) {
        	// 新建
            var grid = me.getParentForm().categoryGrid;
            var item = grid.getSelectionModel().getSelection();
            if (item == null || item.length != 1) {
                return;
            }
            Ext.getCmp("editCategory").setValue(item[0].get("id"));
            var grid = me.getParentForm().lineGrid;
            var item = grid.getSelectionModel().getSelection();
            if (item == null || item.length != 1) {
                return;
            }
            Ext.getCmp("editLine").setValue(item[0].get("id"));

        } else {
        	// 编辑
            var el = me.getEl();
            el.mask(ERP.Const.LOADING);
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Site/siteInfo",
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
                        Ext.getCmp("editAttr").setValue(data.attr);
                        Ext.getCmp("editFreight").setValue(data.freight);
                        Ext.getCmp("editHouses").setValue(data.houses);
                        Ext.getCmp("editLine").setValue(data.lineId);
                        Ext.getCmp("editSort").setValue(data.sort);
                    }

                    el.unmask();
                }
            });
        }
        
        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Site/editSite",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                ERP.MsgBox.tip("数据保存成功");
                me.focus();
                me.__lastId = action.result.id;
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
        if (e.getKey() === e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                var me = this;
                me.onOK(me.adding);
            }
        }
    },
    clearEdit: function () {
        Ext.getCmp("editCode").focus();

        var editors = ["editCode", "editName", "editAddress", "editAddressShipping", "editContact01"];
        for (var i = 0; i < editors.length; i++) {
            var edit = Ext.getCmp(editors[i]);
            if (edit) {
                edit.setValue(null);
                edit.clearInvalid();
            }
        }
    },
    onWndClose: function() {
        var me = this;
        if (me.__lastId) {
            me.getParentForm().freshSiteGrid(me.__lastId);
        }
    }
});