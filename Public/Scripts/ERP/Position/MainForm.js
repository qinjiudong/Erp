// 仓位档案 - 主界面
Ext.define("ERP.Position.MainForm", {
    extend: "Ext.panel.Panel",
    getBaseURL: function () {
        return ERP.Const.BASE_URL;
    },
    initComponent: function () {
        var me = this;

//        Ext.define("ERPWherehouse", {
//            extend: "Ext.data.Model",
//            fields: ["id", "name"]
//        });
//        var wherehouseStore = Ext.create("Ext.data.Store", {
//            model: "ERPWherehouse",
//            autoLoad: false,
//            data: []
//        });
//        me.wherehouseStore = wherehouseStore;
        Ext.define("ERPOrgModel", {
            extend: "Ext.data.Model",
            fields: ["id", "text", "fullName", "orgCode", "leaf", "children", "goods_num"]
        });
//        var wherehouseStore = me.wherehouseStore;
//        Ext.Ajax.request({
//            url: ERP.Const.BASE_URL + "Home/Warehouse/queryData",
//            method: "POST",
//            callback: function (options, success, response) {
//                wherehouseStore.removeAll();
//                if (success) {
//                    var data = Ext.JSON.decode(response.responseText);
//                    wherehouseStore.add(data);
//                        if (wherehouseStore.getCount() > 0) {
//                            var wherehouseStoreId = wherehouseStore.getAt(0).get("id");
//                            if (Ext.getCmp("selectWherehouse").getValue() == null) {
//                           		Ext.getCmp("selectWherehouse").setValue(wherehouseStoreId);
//            								}
//                        }
//                }
//            }
//        });

        var orgStore = Ext.create("Ext.data.TreeStore", {
            model: "ERPOrgModel",
            proxy: {
                type: "ajax",
                url: me.getBaseURL() + "Home/Position/positionList"
            }
        });

        orgStore.on("load", me.onOrgStoreLoad, me);

        var orgTree = Ext.create("Ext.tree.Panel", {
            title: "仓位",
            store: orgStore,
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
                        text: "名称",
                        dataIndex: "text",
                        width: 220
                    }, 
                    /*
                    {
                        text: "商品数",
                        dataIndex: "goods_num",
                        flex: 1
                    }
                    */
                    ]
            }
        });
        me.orgTree = orgTree;

        orgTree.on("select", function (rowModel, record) {
            me.onOrgTreeNodeSelect(record);
        }, me);

        orgTree.on("itemdblclick", me.onEditPosition, me);

        Ext.define("ERPUser", {
            extend: "Ext.data.Model",
            fields: ["name", "code"]
        });
        var storeGrid = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPUser",
            data: []
        });

        var grid = Ext.create("Ext.grid.Panel", {
            title: "商品列表",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 40}),
                {header: "商品编码", dataIndex: "code", menuDisabled: true, sortable: false, width: 200},
                {header: "商品名", dataIndex: "name", menuDisabled: true, sortable: false}
            ],
            store: storeGrid,
            listeners: {
                itemdblclick: {
                    fn: me.onEditGood,
                    scope: me
                }
            }
        });

        this.grid = grid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "新增仓位", iconCls: "ERP-button-add", handler: me.onAddPosition, scope: me},
                {text: "编辑仓位", iconCls: "ERP-button-edit", handler: me.onEditPosition, scope: me},
                {text: "删除仓位", iconCls: "ERP-button-delete", handler: me.onDeletePosition, scope: me}, "-",
                {text: "新增商品", iconCls: "ERP-button-add-user", handler: me.onAddGood, scope: me},
                {text: "修改商品", iconCls: "ERP-button-edit-user", handler: me.onEditGood, scope: me},
                {text: "删除商品", iconCls: "ERP-button-delete-user", handler: me.onDeleteGood, scope: me}, "-",
                {text: "新增仓库", iconCls: "ERP-button-add-detail", handler: this.onAddWarehouse, scope: me}
            ], 
            items: [
//            {
//            	region: "north", height: 60, border: 0,
//            	collajyerpble: true,
//            	title: "查询条件",
//            	layout : {
//							type : "table",
//							columns : 5
//						},
//										items: [
//                        {
//                            id: "selectWherehouse",
//                            xtype: "combo",
//                            fieldLabel: "选择仓库",
//                            allowBlank: false,
//                            //blankText: "没有输入商品分类",
//                            beforeLabelTextTpl: ERP.Const.REQUIRED,
//                            valueField: "id",
//                            displayField: "name",
//														margin: "5, 0, 0, 0",
//                            store: wherehouseStore,
//                            queryMode: "local",
//                            editable: false,
//                            name: "wherehouse_id",
//                            width:"500",
//                            listeners: {
//                                select: {
//                                    fn: me.freshCategoryGrid,
//                                    scope: me
//                                },   
//                            }
//                        },
//				{
//            		id: "editQueryCode",
//					labelWidth : 60,
//					labelAlign : "right",
//					labelSeparator : "",
//					fieldLabel : "仓位编码",
//					margin: "5, 0, 0, 0",
//					xtype : "textfield",
//					listeners: {
//                        specialkey: {
//                            fn: me.onQueryEditSpecialKey,
//                            scope: me
//                        }
//                    }
//				},{
//					id: "editQueryName",
//					labelWidth : 60,
////				labelAlign : "right",
//				labelSeparator : "",
//				fieldLabel : "仓位名称",
//				margin: "5, 0, 0, 0",
//				xtype : "textfield",
//				listeners: {
//                      specialkey: {
//                          fn: me.onQueryEditSpecialKey,
//                          scope: me
//                      }
//                  }
//			},{
//				id: "editQueryAddress",
//				labelWidth : 60,
//				labelAlign : "right",
//				labelSeparator : "",
//				fieldLabel : "商品",
//				margin: "5, 0, 0, 0",
//				xtype : "textfield",
//				listeners: {
//                      specialkey: {
//                          fn: me.onQueryEditSpecialKey,
//                          scope: me
//                      }
//                  }
//			},{
//				xtype: "container",
//				items: [{
//					xtype: "button",
//					text: "查询",
//					width: 100,
//					iconCls: "ERP-button-refresh",
//					margin: "5, 0, 0, 20",
//					handler: me.onQuery,
//					scope: me
//				},{
//					xtype: "button",
//					text: "清空查询条件",
//					width: 100,
//					iconCls: "ERP-button-cancel",
//					margin: "5, 0, 0, 5",
//					handler: me.onClearQuery,
//					scope: me
//				}]
//			}]
//				},
								{
                    region: "center", xtype: "panel", layout: "fit", border: 0,
                    items: [grid]
                }, {
                    xtype: "panel",
                    region: "west",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [orgTree]
                }]
        });

        me.callParent(arguments);
    },
    getGrid: function () {
        return this.grid;
    },
    onAddOrg: function () {
        var form = Ext.create("ERP.User.OrgEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditPosition: function () {
        var tree = this.orgTree;
        var item = tree.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要编辑的仓位");
            return;
        }

        var org = item[0];
        var form = Ext.create("ERP.Position.PositionEditForm", {
            parentForm: this,
            entity: org
        });
        form.show();
    },
    onDeletePosition: function () {
        var me = this;
        var tree = me.orgTree;
        var item = tree.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要删除的仓位");
            return;
        }

        var org = item[0].getData();

        ERP.MsgBox.confirm("请确认是否删除仓位 <span style='color:red'>" + org.name + "</span> ?",
                function () {
                    Ext.getBody().mask("正在删除中...");
                    Ext.Ajax.request({
                        url: me.getBaseURL() + "Home/Position/deletePosition",
                        method: "POST",
                        params: {id: org.id},
                        callback: function (options, success, response) {
                            Ext.getBody().unmask();

                            if (success) {
                                var data = Ext.JSON.decode(response.responseText);
                                if (data.success) {
                                    ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                        me.freshOrgGrid();
                                    });
                                } else {
                                    ERP.MsgBox.showInfo(data.msg);
                                }
                            }
                        }
                    });
                });
    },
    freshOrgGrid: function () {
        this.orgTree.getStore().reload();
    },
    freshUserGrid: function () {
        var tree = this.orgTree;
        var item = tree.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            return;
        }

        this.onOrgTreeNodeSelect(item[0]);
    },
    // private
    onEditGood: function () {
        var item = this.grid.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要编辑的商品");
            return;
        }

        var user = item[0].data;

        var tree = this.orgTree;
        var node = tree.getSelectionModel().getSelection();
            var org = node[0].data;
            user.position_name = org.text;
            user.position_id = org.id;
        var editFrom = Ext.create("ERP.Position.GoodsEditForm", {
            parentForm: this,
            entity: user
        });
        editFrom.show();
    },
    onDeleteGood: function () {
        var me = this;
        var item = me.grid.getSelectionModel().getSelection();
        if (item === null || item.length !== 1) {
            ERP.MsgBox.showInfo("请选择要删除的商品");
            return;
        }

        var user = item[0].getData();

        ERP.MsgBox.confirm("请确认是否删除商品 <span style='color:red'>" + user.name + "</span> ?",
                function () {
                    Ext.getBody().mask("正在删除中...");
                    Ext.Ajax.request({
                        url: me.getBaseURL() + "Home/Position/deletegood",
                        method: "POST",
                        params: {id: user.id},
                        callback: function (options, success, response) {
                            Ext.getBody().unmask();

                            if (success) {
                                var data = Ext.JSON.decode(response.responseText);
                                if (data.success) {
                                    ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                        me.freshUserGrid();
                                    });
                                } else {
                                    ERP.MsgBox.showInfo(data.msg);
                                }
                            }
                        }
                    });
                });
    },
    // private
    onOrgTreeNodeSelect: function (rec) {
        if (!rec) {
            return;
        }
        var org = rec.data;
        if (!org) {
            return;
        }
        var me = this;
        var grid = me.getGrid();

        //grid.setTitle(org.fullName + " - 商品列表");
				grid.setTitle("商品列表");
        grid.getEl().mask("数据加载中...");

        Ext.Ajax.request({
            url: me.getBaseURL() + "Home/Position/goods",
            params: {position_id: org.id},
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);
                }

                grid.getEl().unmask();
            }
        });
    },
    // private
    onOrgStoreLoad: function () {
        var tree = this.orgTree;
        var root = tree.getRootNode();
        if (root) {
            var node = root.firstChild;
            if (node) {
                this.onOrgTreeNodeSelect(node);
            }
        }
    },
    onAddWarehouse: function () {
        var form = Ext.create("ERP.Position.WarehouseEditForm", {
            parentForm: this
        });

        form.show();
    },
    onAddPosition: function () {
        var form = Ext.create("ERP.Position.PositionEditForm", {
            parentForm: this
        });

        form.show();
    },
    onAddGood: function () {
        var form = Ext.create("ERP.Position.GoodsEditForm", {
            parentForm: this
        });

        form.show();
    },
});