// 店铺档案 - 主界面
Ext.define("ERP.Shop.MainForm", {
    extend: "Ext.panel.Panel",
    initComponent: function () {
        var me = this;

        Ext.define("ERPShopCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "name", "code", {name: "cnt", type: "int"}]
        });

        var categoryGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "店铺",
            features: [{ftype: "summary"}],
            forceFit: true,
            columnLines: true,
            columns: [
                {header: "店铺编码", dataIndex: "code", width: 60, menuDisabled: true, sortable: false},
                {header: "名称", dataIndex: "name", flex: 1, menuDisabled: true, sortable: false}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPShopCategory",
                autoLoad: false,
                data: []
            }),
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
        me.categoryGrid = categoryGrid;

        Ext.define("ERPShop", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "spec", "barcode", "unitId", "unitName", "categoryId", "status_str", 'oversold_str','buyPrice','gross',
                    "salePrice", "basecode", "packrate", "bulk" ,"bulk_str", "is_delete","promotePrice", "promoteBeginTime", "promoteEndTime","balance_count","supplier_code","supplier_name"]
        });

        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPShop",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Shop/goods",
                reader: {
                    root: 'goodsList',
                    totalProperty: 'totalCount'
                }
            },
            listeners: {
                beforeload: {
                	fn: function () {
                    	store.proxy.extraParams = me.getQueryParam();
                    },
                    scope: me
                },
                load: {
                    fn: function (e, records, successful) {
                        if (successful) {
                            me.refreshCategoryCount();
                            me.gotoShopGridRecord(me.__lastId);
                        }
                    },
                    scope: me
                }
            }
        });

        var shopGrid = Ext.create("Ext.grid.Panel", {
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
                {header: "毛利", dataIndex: "gross", menuDisabled: true, sortable: false, width: 60},
                {header: "负库存", dataIndex: "oversold_str", menuDisabled: true, sortable: false, width: 30},
                {header: "上架", dataIndex: "status_str", menuDisabled: true, sortable: false, width: 30},
                {header: "库存", dataIndex: "balance_count", menuDisabled: true, sortable: false, width: 30},
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
                    fn: me.onEditShop,
                    scope: me
                }
            }
        });


        me.shopGrid = shopGrid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "新增店铺", iconCls: "ERP-button-add", handler: me.onAddShop, scope: me},
                {text: "编辑店铺", iconCls: "ERP-button-edit", handler: me.onEditShop, scope: me},
                {text: "删除店铺", iconCls: "ERP-button-delete", handler: me.onDeleteShop, scope: me}, "-",
//                {text: "调拨商品", iconCls: "ERP-button-add-user", handler: me.onTransferGood, scope: me},
//                {text: "删除商品", iconCls: "ERP-button-delete-user", handler: me.onDeleteGood, scope: me}
            ], 
            items: [
            {
            	region: "north", height: 60, border: 0,
            	collajyerpble: true,
            	title: "查询条件",
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
        				},
                        {
                            id: "editQueryBarCode",
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
					width: 100,
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
                            }]
			}]
				},
								{
                    region: "center", xtype: "panel", layout: "fit", border: 0,
                    items: [shopGrid]
                }, {
                    xtype: "panel",
                    region: "west",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [categoryGrid]
                }]
        });

        me.callParent(arguments);

        me.__queryEditNameList = ["editQueryCode", "editQueryName", "editQueryBarCode", "editQueryDelete"];

        me.freshCategoryGrid();
    },
    onTransferGood: function () {
        var form = Ext.create("ERP.Shop.CategoryEditForm", {
            parentForm: this
        });

        form.show();
    },
    onAddCategory: function () {
        var form = Ext.create("ERP.Shop.CategoryEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditCategory: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的供应商分类");
            return;
        }

        var category = item[0];

        var form = Ext.create("ERP.Shop.CategoryEditForm", {
            parentForm: this,
            entity: category
        });

        form.show();
    },
    onDeleteCategory: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的供应商分类");
            return;
        }

        var category = item[0];
        var info = "请确认是否删除供应商分类: <span style='color:red'>" + category.get("name") + "</span>";
        var me = this;

        var store = me.categoryGrid.getStore();
        var index = store.findExact("id", category.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Shop/deleteCategory",
                method: "POST",
                params: {id: category.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshCategoryGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    }
                }
            });
        });
    },
    freshCategoryGrid: function (id) {
    	var me = this;
        var grid = me.categoryGrid;
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Shop/ShopList",
            method: "POST",
            params: me.getQueryParam(),
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (id) {
                        var r = store.findExact("id", id);
                        if (r != -1) {
                            grid.getSelectionModel().select(r);
                        }
                    } else {
                        grid.getSelectionModel().select(-1);
                    }
                }

                el.unmask();
            }
        });
    },
    freshShopGrid: function (id) {

        var grid = this.shopGrid;
        grid.setTitle("商品列表");

        this.__lastId = id;
        Ext.getCmp("pagingToolbar").doRefresh()
    },
    // private
    onCategoryGridSelect: function () {
        var me = this;
        me.shopGrid.getStore().currentPage = 1;
        me.freshShopGrid();
    },
    onAddShop: function () {
        var form = Ext.create("ERP.Shop.ShopEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditShop: function () {
        var tree = this.categoryGrid;
        var item = tree.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要编辑的店铺");
            return;
        }

        var org = item[0];
        var form = Ext.create("ERP.Shop.ShopEditForm", {
            parentForm: this,
            entity: org
        });
        form.show();
    },
    onDeleteShop: function () {
        var me = this;
        var tree = me.categoryGrid;
        var item = tree.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要删除的店铺");
            return;
        }

        var org = item[0].getData();
        ERP.MsgBox.confirm("请确认是否删除店铺 <span style='color:red'>" + org.name + "</span> ?",
                function () {
                    Ext.getBody().mask("正在删除中...");
                    Ext.Ajax.request({
                        url: ERP.Const.BASE_URL + "Home/Shop/deleteShop",
                        method: "POST",
                        params: {id: org.id},
                        callback: function (options, success, response) {
                            Ext.getBody().unmask();

                            if (success) {
                                var data = Ext.JSON.decode(response.responseText);
                                if (data.success) {
                                    ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                        me.freshCategoryGrid();
                                    });
                                } else {
                                    ERP.MsgBox.showInfo(data.msg);
                                }
                            }
                        }
                    });
                });
    },
    gotoCategoryGridRecord: function (id) {
        var me = this;
        var grid = me.categoryGrid;
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
    gotoShopGridRecord: function (id) {
        var me = this;
        var grid = me.shopGrid;
        var store = grid.getStore();
        if (id) {
            var r = store.findExact("id", id);
            if (r != -1) {
                grid.getSelectionModel().select(r);
            } else {
                grid.getSelectionModel().select(0);
            }
        } else {
            grid.getSelectionModel().select(0);
        }
    },
    refreshCategoryCount: function() {
        var me = this;
        var item = me.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }

        var category = item[0];
        category.set("cnt", me.shopGrid.getStore().getTotalCount());
        me.categoryGrid.getStore().commitChanges();
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
        var item = me.categoryGrid.getSelectionModel().getSelection();
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
        }
        
        var barcode = Ext.getCmp("editQueryBarCode").getValue();
        if (barcode) {
        	result.barcode = barcode;
        }
        
        var name = Ext.getCmp("editQueryName").getValue();
        if (name) {
        	result.name = name;
        }
        var is_delete = Ext.getCmp("editQueryDelete").getValue();
        if (is_delete) {
        	result.is_delete = is_delete;
        }
        
        return result;
    },
    
    onQuery: function() {
//    	this.freshCategoryGrid();
        var me = this;
        me.shopGrid.getStore().currentPage = 1;  
        me.freshShopGrid();
    },

    onExport:function(){
        var me = this;
        var params = me.getQueryParam();
        var send_parm = "";
        for(i in params){
            send_parm+="&"+i+"="+params[i];
        }
        url = ERP.Const.BASE_URL + "Home/Shop/goods?act=export" + send_parm + "&start=0&limit=10000";
        window.open(url);
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
    }
});