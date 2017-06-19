// 自定义字段 - 上级仓位机构字段
Ext.define("ERP.Shop.CategoryField", {
    extend: "Ext.form.field.Trigger",
    alias: "widget.jyerp_goods_category_field",
    config: {
    	parentCmp: null
    },
    initComponent: function () {
        this.enableKeyEvents = true;

        this.callParent(arguments);

        this.on("keydown", function (field, e) {
            if (e.getKey() === e.BACKSPACE) {
                e.preventDefault();
                return false;
            }

            if (e.getKey() !== e.ENTER) {
                this.onTriggerClick(e);
            }
        });
    },

    onTriggerClick: function (e) {
        Ext.define("ShopCategoryModel", {
            extend: "Ext.data.Model",
            fields: ["id","orgCode", "text"]
        });
        var me = this;
//        var categoryStore = Ext.create("Ext.data.Store", {
//            model: "ShopCategoryModel",
//            proxy: {
//                type: "ajax",
//                url:ERP.Const.BASE_URL + "Home/Shop/getShop",
//                method:"POST"
//            }
//        });

        var categoryStore = Ext.create("Ext.data.Store", {
            model: ShopCategoryModel,
            autoLoad: false,
            data: []
        });

        var categoryTree = Ext.create("Ext.grid.Panel", {
            columnLines: true,
            border: 0,
            store: categoryStore,
            columns: [{ header: "编码", dataIndex: "orgCode", menuDisabled: true},
                      { header: "店铺", dataIndex: "text",menuDisabled: true, flex: 1}
            ],
            listeners: {
                select: {
                    fn: me.onCategoryGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditShop,
                    scope: me
                }
            }
        });
        me.categoryTree = categoryTree;

        categoryTree.on("itemdblclick", this.onOK, this);
        this.categoryTree = categoryTree;

        var wnd = Ext.create("Ext.window.Window", {
            title: "选择店铺",
            modal: true,
            width: 400,
            height: 300,
            layout: "fit",
            items: [
                {
                    region: "center",
                    xtype: "panel",
                    layout: "fit",
                    border: 0,
                    items: [categoryTree]
                },
                {
                    xtype: "panel",
                    region: "south",
                    height: 40,
                    layout: "fit",
                    border: 0,
                    items: [
                        {
                            xtype: "form",
                            layout: "form",
                            bodyPadding: 5,
                            items: [
                                {
                                    id: "__editShop",
                                    xtype: "textfield",
                                    fieldLabel: "店铺",
                                    labelWidth: 50,
                                    labelAlign: "right",
                                    labelSeparator: ""
                                }
                            ]
                        }
                    ]
                }],
            buttons: [
                {
                    text: "确定", handler: this.onOK, scope: this
                },
                {
                    text: "取消", handler: function () { wnd.close(); }
                }
            ]
        });
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Shop/ShopList",
                params: {
                    queryKey: ''
                },
                method: "POST",
                callback: function (opt, success, response) {
                    categoryStore.removeAll();
                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        categoryStore.add(data);
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                    }
                },
                scope: this
            });

        wnd.on("close", function () {
            me.focus();
        });
        
        this.wnd = wnd;
        wnd.show();
    },

    // private
    onOK: function () {
    	var me = this;
        var categoryTree = this.categoryTree;
        var item = categoryTree.getSelectionModel().getSelection();
        var data = item[0].data;
        me.focus();
        me.wnd.close();
        me.focus();
        if(me.getParentCmp() && me.getParentCmp().setParentShopCategory){
        	me.getParentCmp().setParentShopCategory(data,'out');
        	return false;
        }
    },

    // private
    onNone: function () {
    	var me = this;
    	if(me.getParentCmp() && me.getParentCmp().setParentShopCategory){
        	me.getParentCmp().setParentShopCategory({id: "", name: ""});
        	return false;
        }
        this.wnd.close();
        this.focus();
    }
});