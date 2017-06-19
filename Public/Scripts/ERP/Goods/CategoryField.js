// 自定义字段 - 上级仓位机构字段
Ext.define("ERP.Goods.CategoryField", {
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
        Ext.define("GoodsCategoryModel", {
            extend: "Ext.data.Model",
            fields: ["id","code", "text", "name", "parent_id"]
        });
        var me = this;
        var categoryStore = Ext.create("Ext.data.TreeStore", {
            model: "GoodsCategoryModel",
            proxy: {
                type: "ajax",
                url:ERP.Const.BASE_URL + "Home/Goods/getCategoryTree",
                method:"POST"
            }
        });

        var categoryTree = Ext.create("Ext.tree.Panel", {
            title: "商品分类",
            store: categoryStore,
            rootVisible: false,
            useArrows: true,
            viewConfig: {
                loadMask: true
            },
            columns: {
                defaults: {
                    sortable: false,
                    menuDisabled: true,
                    draggable: false
                },
                items: [{
                        xtype: "treecolumn",
                        text: "编码",
                        dataIndex: "code",
                        width: 220
                    }, {
                        text: "名称",
                        dataIndex: "name",
                        flex: 1
                    }]
            },
            listeners: {
                select: {
                    fn: me.onCategoryGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditCategory,
                    scope: me
                }
            }
        });
        me.categoryTree = categoryTree;

        categoryTree.on("itemdblclick", this.onOK, this);
        this.categoryTree = categoryTree;

        var wnd = Ext.create("Ext.window.Window", {
            title: "选择上级分类",
            modal: true,
            width: 400,
            height: 300,
            layout: "fit",
            items: [categoryTree],
            buttons: [
                {
                    text: "确定", handler: this.onOK, scope: this
                },
                {
                    text: "取消", handler: function () { wnd.close(); }
                }
            ]
        });
        this.wnd = wnd;
        wnd.show();
    },

    // private
    onOK: function () {
    	var me = this;
        var categoryTree = this.categoryTree;
        var item = categoryTree.getSelectionModel().getSelection();

        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("没有选择上级分类");

            return;
        }

        var data = item[0].data;
        me.focus();
        me.wnd.close();
        me.focus();
        if(me.getParentCmp() && me.getParentCmp().setParentGoodsCategory){
        	me.getParentCmp().setParentGoodsCategory(data);
        	return false;
        }
    },

    // private
    onNone: function () {
    	var me = this;
    	if(me.getParentCmp() && me.getParentCmp().setParentGoodsCategory){
        	me.getParentCmp().setParentGoodsCategory({id: "", name: ""});
        	return false;
        }
        this.wnd.close();
        this.focus();
    }
});