// 商品 - 主界面
Ext.define("ERP.Goods.MainForm", {
    extend: "Ext.panel.Panel",
    
    initComponent: function () {
        var me = this;

        Ext.define("ERPGoodsCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "parent_id", "parent_name", {name: "cnt", type: "int"}]
        });

        var categoryStore = Ext.create("Ext.data.TreeStore", {
            model: "ERPGoodsCategory",
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

        Ext.define("ERPGoods", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "spec", "barcode", "unitId", "unitName", "categoryId", "status_str", 'oversold_str','buyPrice','gross','buytax',
                    "salePrice", "basecode", "packrate", "bulk" ,"bulk_str", "is_delete","promotePrice", "promoteBeginTime", "promoteEndTime","balance_count","supplier_code","supplier_name"]
        });

        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPGoods",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Goods/goodsList",
                reader: {
                    root: 'goodsList',
                    totalProperty: 'totalCount'
                }
            }
        });

        store.on("beforeload", function () {
            store.proxy.extraParams = me.getQueryParam();
        });
        store.on("load", function (e, records, successful) {
            if (successful) {
                me.refreshCategoryCount();
                me.gotoGoodsGridRecord(me.__lastId);
            }
        });

        var goodsGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "商品列表",
            bbar: [{
                    id: "pagingToolbar",
                    border: 0,
                    xtype: "pagingtoolbar",
                    store: store
                }, "-", {
                    xtype: "displayfield",
                    value: "每页显示"
                }, {
                    id: "comboCountPerPage",
                    xtype: "combobox",
                    editable: false,
                    width: 60,
                    store: Ext.create("Ext.data.ArrayStore", {
                        fields: ["text"],
                        data: [["20"], ["50"], ["100"], ["300"], ["1000"]]
                    }),
                    value: 20,
                    listeners: {
                        change: {
                            fn: function () {
                                store.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                store.currentPage = 1;
                                Ext.getCmp("pagingToolbar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }],
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "商品编码", dataIndex: "code", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "barcode", menuDisabled: true, sortable: false},
                {header: "品名", dataIndex: "name", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "spec", menuDisabled: true, sortable: false, width: 50},
                {header: "计量单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "属性", dataIndex: "bulk_str", menuDisabled: true, sortable: false, width: 60},
                {header: "进价", dataIndex: "buyPrice", menuDisabled: true, sortable: false, width: 60},
                {header: "销售价", dataIndex: "salePrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn"},
                {header: "毛利", dataIndex: "gross", sortable: true, width: 60},
                {header: "进项税率", dataIndex: "buytax", sortable: true, width: 60},
                {header: "负库存", dataIndex: "oversold_str", sortable: true, width: 30},
                {header: "上架", dataIndex: "status_str", sortable: true, width: 30},
                {header: "库存", dataIndex: "balance_count", sortable: true, width: 30},
                {header: "供应商编码", dataIndex: "supplier_code", menuDisabled: true, sortable: false, width: 50},
                {header: "供应商名称", dataIndex: "supplier_name", menuDisabled: true, sortable: false, width: 50},
            ],
            store: store,
            listeners: {
                select:{
                    fn: me.onGoodsSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditGoods,
                    scope: me
                }
            }
        });

        me.goodsGrid = goodsGrid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "新增商品分类", iconCls: "ERP-button-add", handler: me.onAddCategory, scope: me},
                {text: "编辑商品分类", iconCls: "ERP-button-edit", handler: me.onEditCategory, scope: me},
                //{text: "删除商品分类", iconCls: "ERP-button-delete", handler: me.onDeleteCategory, scope: me}, "-",
                {text: "新增商品", iconCls: "ERP-button-add-detail", handler: me.onAddGoods, scope: me},
                {text: "修改商品", iconCls: "ERP-button-edit-detail", handler: me.onEditGoods, scope: me},
                {text: "废弃商品", iconCls: "ERP-button-delete-detail", handler: me.onDeleteGoods, scope: me},
                {text: "恢复废弃商品", iconCls: "ERP-button-add-detail", disabled: true, id: "recoverGoodsButton", handler: me.onCancelDeleteGoods, scope: me}, "-",
                
                {
                    text: "秤码导出",
                    iconCls: "ERP-button-exit",
                    handler: function() {
                        window.open("/erp/Home/Goods/chengma");
                    }
                },
                
                "-",
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }

                }
            ],
            items: [{
                    	region: "north",
                    	border: 0,
                    	height: 60,
                    	title: "查询条件",
                    	collajyerpble: true,
                    	layout : {
        					type : "table",
        					columns : 6
        				},
                    	items: [{
                    		id: "editQueryCode",
        					labelWidth : 60,
        					labelAlign : "right",
        					labelSeparator : "",
        					fieldLabel : "商品编码",
        					margin: "5, 0, 0, 0",
        					xtype : "textfield",
        					listeners: {
                                specialkey: {
                                    fn: me.onQueryEditSpecialKey,
                                    scope: me
                                }
                            }
        				},{
        					id: "editQueryName",
        					labelWidth : 60,
        					labelAlign : "right",
        					labelSeparator : "",
        					fieldLabel : "品名",
        					margin: "5, 0, 0, 0",
        					xtype : "textfield",
        					listeners: {
                                specialkey: {
                                    fn: me.onQueryEditSpecialKey,
                                    scope: me
                                }
                            }
        				},{
        					id: "editQuerySpec",
        					labelWidth : 60,
        					labelAlign : "right",
        					labelSeparator : "",
        					fieldLabel : "规格型号",
        					margin: "5, 0, 0, 0",
        					xtype : "hidden",
        					listeners: {
                                specialkey: {
                                    fn: me.onLastQueryEditSpecialKey,
                                    scope: me
                                }
                            }
        				},
                        {
                            id: "editQueryBarcode",
                            labelWidth : 60,
                            labelAlign : "right",
                            labelSeparator : "",
                            fieldLabel : "商品条码",
                            margin: "5, 0, 0, 0",
                            xtype : "textfield",
                            listeners: {
                                specialkey: {
                                    fn: me.onLastQueryEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editQuerySupplierCode",
                            labelWidth : 60,
                            labelAlign : "right",
                            labelSeparator : "",
                            fieldLabel : "供应商编码",
                            margin: "5, 0, 0, 0",
                            xtype : "textfield",
                            listeners: {
                                specialkey: {
                                    fn: me.onLastQueryEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editQueryDelete",
                            labelWidth : 28,
                            labelAlign : "right",
                            labelSeparator : "",
                            fieldLabel : "废弃",
                            margin: "5, 0, 0, 0",
                            xtype : "checkbox",
                            width:10,
                            listeners: {
                                specialkey: {
                                    fn: me.onLastQueryEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                         {
        					xtype: "container",
        					items: [{
        						xtype: "button",
        						text: "查询",
        						width: 50,
        						iconCls: "ERP-button-refresh",
        						margin: "5, 0, 0, 20",
        						handler: me.onQuery,
        						scope: me
        					},{
        						xtype: "button",
        						text: "清空查询条件",
        						width: 100,
        						iconCls: "ERP-button-cancel",
        						margin: "5, 0, 0, 5",
        						handler: me.onClearQuery,
        						scope: me
        					},{
                                xtype: "button",
                                text: "导出",
                                width: 100,
                                iconCls: "ERP-button-cancel",
                                margin: "5, 0, 0, 5",
                                handler: me.onExport,
                                scope: me
                            },

                            /*
                            {
                                xtype: "button",
                                text: "同步所有商品到电商",
                                width: 130,
                                iconCls: "ERP-button-refresh",
                                margin: "5, 0, 0, 5",
                                handler: me.onSyn,
                                scope: me
                            }
                            */
                            ]
        				}]
                    },{
                    	region: "center", layout: "border",
                    	items: [{
                            region: "center", xtype: "panel", layout: "fit", border: 0,
                            items: [goodsGrid]
                        }, {
                            xtype: "panel",
                            region: "west",
                            layout: "fit",
                            width: 300,
                            minWidth: 200,
                            maxWidth: 350,
                            split: true,
                            border: 0,
                            items: [categoryTree]
                        }]
                    }]
        });

        me.callParent(arguments);
        
        me.__queryEditNameList = ["editQueryCode", "editQueryName", "editQuerySpec", "editQueryBarcode"];

        //me.freshCategoryGrid(null, true);
    },
    onAddCategory: function () {
        var form = Ext.create("ERP.Goods.CategoryEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditCategory: function () {
        var item = this.categoryTree.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的商品分类");
            return;
        }

        var category = item[0];

        var form = Ext.create("ERP.Goods.CategoryEditForm", {
            parentForm: this,
            entity: category
        });

        form.show();
    },
    onDeleteCategory: function () {
        var me = this;
        var item = me.categoryTree.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的商品分类");
            return;
        }

        var category = item[0];

        var store = me.categoryTree.getStore();
        var index = store.findExact("id", category.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        var info = "请确认是否删除商品分类: <span style='color:red'>" + category.get("name") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/deleteCategory",
                method: "POST",
                params: {id: category.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作")
                            me.freshCategoryGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            window.location.reload();
                        });
                    }
                }

            });
        });
    },
    freshCategoryGrid: function (id) {
    	var me = this;
        var categoryTree = me.categoryTree;
        var el = categoryTree.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        categoryTree.getStore().reload();
        el.unmask();
    },
    freshGoodsGrid: function () {
        var me = this;
        var item = me.categoryTree.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            //var grid = me.goodsGrid;
            //grid.setTitle("商品列表");
            //return;
        }

        Ext.getCmp("pagingToolbar").doRefresh()
    },
    // private
    onCategoryGridSelect: function () {
        var me = this;
        me.goodsGrid.getStore().currentPage = 1;
        
        me.freshGoodsGrid();
    },
    onAddGoods: function () {
        if (this.categoryTree.getStore().getCount() == 0) {
            ERP.MsgBox.showInfo("没有商品分类，请先新增商品分类");
            return;
        }

        var form = Ext.create("ERP.Goods.GoodsEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditGoods: function () {
        var item = this.categoryTree.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择商品分类");
            return;
        }

        var category = item[0];

        var item = this.goodsGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的商品");
            return;
        }

        var goods = item[0];
        goods.set("categoryId", category.get("id"));
        var form = Ext.create("ERP.Goods.GoodsEditForm", {
            parentForm: this,
            entity: goods
        });

        form.show();
    },
    onDeleteGoods: function () {
        var me = this;
        var item = me.goodsGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要废弃的商品");
            return;
        }

        var goods = item[0];

        var store = me.goodsGrid.getStore();
        var index = store.findExact("id", goods.get("id"));
        index--;
        var preItem = store.getAt(index);
        if (preItem) {
            me.__lastId = preItem.get("id");
        }


        var info = "请确认是否删除商品: <span style='color:red'>" + goods.get("name")
                + " " + goods.get("spec") + "</span>";

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/deleteGoods",
                method: "POST",
                params: {id: goods.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshGoodsGrid();
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            window.location.reload();
                        });
                    }
                }

            });
        });
    },
    onCancelDeleteGoods: function () {
        var me = this;
        var item = me.goodsGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要恢复的商品");
            return;
        }

        var goods = item[0];

        var store = me.goodsGrid.getStore();
        var index = store.findExact("id", goods.get("id"));
        index--;
        var preItem = store.getAt(index);
        if (preItem) {
            me.__lastId = preItem.get("id");
        }


        var info = "请确认是否恢复商品: <span style='color:red'>" + goods.get("name")
                + " " + goods.get("spec") + "</span>";

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在恢复中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/deleteGoods",
                method: "POST",
                params: {id: goods.get("id"), "recover":1},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成恢复操作");
                            me.freshGoodsGrid();
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            window.location.reload();
                        });
                    }
                }

            });
        });
    },
    gotoCategoryGridRecord: function (id) {
        var me = this;
        var grid = me.categoryTree;
        var store = grid.getStore();
        if (id) {
            var r = store.findExact("id", id);
            if (r != -1) {
                grid.getSelectionModel().select(r);
            } else {
                grid.getSelectionModel().select(0);
            }
        }
    },
    gotoGoodsGridRecord: function (id) {
        var me = this;
        var grid = me.goodsGrid;
        var store = grid.getStore();
        if (id) {
            var r = store.findExact("id", id);
            if (r != -1) {
                grid.getSelectionModel().select(r);
            } else {
                grid.getSelectionModel().select(0);
            }
        }
    },
    refreshCategoryCount: function() {
        var me = this;
        var item = me.categoryTree.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }

        var category = item[0];
        category.set("cnt", me.goodsGrid.getStore().getTotalCount());
        //me.categoryTree.getStore().commitChanges();
    },
    
    onQueryEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
            var me = this;
            var id = field.getId();
            for (var i = 0; i < me.__queryEditNameList.length - 1; i++) {
                var editorId = me.__queryEditNameList[i];
                if (id === editorId) {
                    var edit = Ext.getCmp(me.__queryEditNameList[i + 1]);
                    edit.focus();
                    edit.setValue(edit.getValue());
                }
            }
        }
    },
    
    onLastQueryEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
        	this.onQuery();
        }
    },

    getQueryParam: function() {
    	var me = this;
        var item = me.categoryTree.getSelectionModel().getSelection();
        var categoryId;
        if (item == null || item.length != 1) {
            categoryId = null;
        } else {
        	categoryId = item[0].get("id");
        }

        var result = {
        	categoryId: categoryId
        };
        
        var code = Ext.getCmp("editQueryCode").getValue();
        if (code) {
        	result.code = code;
            categoryId = null;
        }
        
        var name = Ext.getCmp("editQueryName").getValue();
        if (name) {
        	result.name = name;
            categoryId = null;
        }
        
        var spec = Ext.getCmp("editQuerySpec").getValue();
        if (spec) {
        	result.spec = spec;
            categoryId = null;
        }

        var barcode = Ext.getCmp("editQueryBarcode").getValue();
        if(barcode){
            result.barcode = barcode;
            categoryId = null;
        }
        result.categoryId = categoryId;

        var is_delete = Ext.getCmp("editQueryDelete").getValue();
        if (is_delete) {
            result.is_delete = is_delete;
        }
        var supplier_code = Ext.getCmp("editQuerySupplierCode").getValue();
        if(supplier_code){
            result.supplier_code = supplier_code;
        }
        return result;
    },

    queryGoods: function(){
        var me = this;
        me.goodsGrid.getStore().currentPage = 1;  
        me.freshGoodsGrid();
    },
    onGoodsSelect:function(item){
        var item = this.goodsGrid.getSelectionModel().getSelection();
        var goods = item[0];
        var is_delete = goods.get("is_delete");
        if(is_delete == 1){
            Ext.getCmp("recoverGoodsButton").enable();
        } else {
            Ext.getCmp("recoverGoodsButton").disable();
        }
    },
    onQuery: function() {
    	//this.freshCategoryGrid();
        this.queryGoods();
    },
    
    onClearQuery: function() {
    	var nameList = this.__queryEditNameList;
    	for (var i = 0; i < nameList.length; i++) {
    		var name = nameList[i];
    		var edit = Ext.getCmp(name);
    		if (edit) {
    			edit.setValue(null);
    		}
    	}
    	
    	this.onQuery();
    },

    onSyn: function(){
        var me = this;
        var info = "确实要把所有商品同步到电商平台吗？这会花费很长的时间";
        me.__page = 1;
        me.__total = 0;
        me.__limit = 20;
        ERP.MsgBox.confirm(info, function () {
            me.doSyn();
        });
    },
    doSyn: function(){
        var me = this;
        var el = Ext.getBody();
            el.mask("正在同步中...("+(me.__page * me.__limit)+"/"+me.__total+")");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/batchSynGoods",
                method: "POST",
                params: {page:me.__page},
                callback: function (options, success, response) {
                    //el.unmask();
                    me.__page++;
                    if (success) {

                        var data = Ext.JSON.decode(response.responseText);
                        me.__total = data.data.total;
                        if(me.__total < me.__page * me.__limit){
                            el.unmask();
                            ERP.MsgBox.showInfo("同步完成");
                            return false;
                        }
                        if (data.success) {
                            me.doSyn();
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                            me.doSyn();
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            //window.location.reload();
                            me.doSyn();
                        });
                    }
                }

            });
    },

    onExport:function(){
        var me = this;
        var params = me.getQueryParam();
        var send_parm = "";
        for(i in params){
            send_parm+="&"+i+"="+params[i];
        }
        url = ERP.Const.BASE_URL + "Home/Goods/goodsList?act=export" + send_parm + "&start=0&limit=10000";
        window.open(url);
    }

});