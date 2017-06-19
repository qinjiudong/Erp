// 商品分类 - 编辑界面
Ext.define("ERP.Goods.CategoryEditForm", {
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
            title: entity == null ? "新增商品分类" : "编辑商品分类",
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
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        }, 

                        {
                            xtype: "hidden",
                            name: "parentId",
                            id: "editParentCategoryId",
                            value: entity == null ? null : entity.get("parent_id"),
                        }, 

                        {
                            id: "editParentCategoryName",
                            xtype: "jyerp_goods_category_field",
                            parentCmp: me,
                            fieldLabel: "上级分类",
                            name:"parentName",
                            value: entity == null ? null : entity.get("parent_name"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditParentOrgSpecialKey,
                                    scope: me
                                }
                            }
                        },

                        {
                            id: "editCode",
                            fieldLabel: "分类编码",
                            allowBlank: false,
                            blankText: "没有输入分类编码",
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
                            fieldLabel: "分类名称",
                            allowBlank: false,
                            blankText: "没有输入分类名称",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity == null ? null : entity.get("name"),
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
            url: ERP.Const.BASE_URL + "Home/Goods/editCategory",
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
                    me.getParentForm().freshCategoryGrid(me.__lastId);
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
    setParentGoodsCategory: function(data){
        Ext.getCmp("editParentCategoryName").setValue(data.name);
        Ext.getCmp("editParentCategoryId").setValue(data.id);
    }
});