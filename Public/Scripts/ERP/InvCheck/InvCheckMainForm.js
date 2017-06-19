// 库存盘点 - 主界面
Ext.define("ERP.InvCheck.InvCheckMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [{
                    text: "新建盘点单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddBill
                }, "-", {
                    text: "编辑盘点单",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onEditBill
                }, "-", {
                    text: "删除盘点单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteBill
                }, "-", {
                    text: "提交盘点单入库",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: me.onCommit
                }, "-", {
                    text: "关闭",
                    iconCls: "ERP-button-exit",
                    handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }}],
            items: [{
                    region: "north",
                    height: "30%",
                    split: true,
                    layout: "fit",
                    border: 0,
                    items: [me.getMainGrid()]
                }, {
                    region: "center",
                    layout: "fit",
                    border: 0,
                    items: [me.getDetailGrid()]
                }]
        });

        me.callParent(arguments);

        me.refreshMainGrid();
    },
    
    refreshMainGrid: function (id) {
    	var me = this;
        var gridDetail = me.getDetailGrid();
        gridDetail.setTitle("盘点单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        me.__lastId = id;
    },

    
    // 新增盘点单
    onAddBill: function () {
    	var form = Ext.create("ERP.InvCheck.ICEditForm",{
    		parentForm: this
    	});
    	form.show();
    },
    
    // 编辑盘点单
    onEditBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的盘点单");
            return;
        }
        var bill = item[0];
        
        var form = Ext.create("ERP.InvCheck.ICEditForm",{
    		parentForm: me,
    		entity: bill
    	});
    	form.show();
    },
    
    // 删除盘点单
    onDeleteBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的盘点单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否删除盘点单: <span style='color:red'>" + bill.get("ref")
                + "</span>";
        
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvCheck/deleteICBill",
                method: "POST",
                params: {
                    id: bill.get("id")
                },
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                me.refreshMainGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                    }
                }

            });
        });
    },
    
    // 提交盘点单
    onCommit: function () {
        var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的盘点单");
            return;
        }
        var bill = item[0];

        var detailCount = me.getDetailGrid().getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前盘点单没有录入明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的盘点单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvCheck/commitICBill",
                timeout:300000,
                method: "POST",
                params: {id: bill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshMainGrid(data.id);
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                    }
                }
            });
        });
    },
    
    getMainGrid: function() {
        var me = this;
        if (me.__mainGrid) {
            return me.__mainGrid;
        }

        var modelName = "ERPITBill";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate",  "warehouseName",
                "inputUserName", "bizUserName", "billStatus"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/InvCheck/icbillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        store.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoMainGridRecord(me.__lastId);
            }
        });

        me.__mainGrid = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [{
                    header: "状态",
                    dataIndex: "billStatus",
                    menuDisabled: true,
                    sortable: false,
                    width: 60,
                    renderer: function(value) {
                        if(value == "盘点中"){
                            value = "<span style='color:#aaa'>" + value + "</span>";
                        } else if (value == "审核中"){
                            value = "<span style='color:red'>" + value + "</span>";
                        } else if (value == "盘点结束"){
                            value = "<span style='color:green'>" + value + "</span>";
                        }
                        return value;
                    }
                }, {
                    header: "单号",
                    dataIndex: "ref",
                    width: 110,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "业务日期",
                    dataIndex: "bizDate",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "盘点仓库",
                    dataIndex: "warehouseName",
                    menuDisabled: true,
                    sortable: false,
                    width: 150
                }, {
                    header: "业务员",
                    dataIndex: "bizUserName",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "录单人",
                    dataIndex: "inputUserName",
                    menuDisabled: true,
                    sortable: false
                }],
            listeners: {
                select: {
                    fn: me.onMainGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditBill,
                    scope: me
                }
            },
            store: store,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
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
                                storeWSBill.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                storeWSBill.currentPage = 1;
                                Ext.getCmp("pagingToobar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }]
        });

        return me.__mainGrid;
    },
    
    getDetailGrid: function() {
        var me = this;
        if (me.__detailGrid) {
            return me.__detailGrid;
        }
        
        var modelName = "ERPITBillDetail";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName", 
                    "nowgoodsCount", "goodsCount", "goodsMoney", "goodsPrice","diffMoney"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: []
        });

        me.__detailGrid = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            title: "盘点单明细",
            columnLines: true,
            columns: [Ext.create("Ext.grid.RowNumberer", {
                    text: "序号",
                    width: 30
                }), {
                    header: "商品编码",
                    dataIndex: "goodsCode",
                    menuDisabled: true,
                    sortable: false,
                    width: 120
                }, {
                    header: "商品名称",
                    dataIndex: "goodsName",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                }, {
                    header: "提交前库存数量",
                    dataIndex: "nowgoodsCount",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                },{
                    header: "规格型号",
                    dataIndex: "goodsSpec",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                }, {
                    header: "盘点后库存数量",
                    dataIndex: "goodsCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150
                }, 
                {
                    header: "单价",
                    dataIndex: "goodsPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150
                },
                {
                    header: "差异金额",
                    dataIndex: "diffMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150
                },
                {
                    header: "单位",
                    dataIndex: "unitName",
                    menuDisabled: true,
                    sortable: false,
                    width: 60
                }],
            store: store
        });

        return me.__detailGrid;
    },
    
    gotoMainGridRecord: function (id) {
        var me = this;
        var grid = me.getMainGrid();
        grid.getSelectionModel().deselectAll();
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
    
    onMainGridSelect: function() {
    	this.refreshDetailGrid();
    },
    
    refreshDetailGrid: function (id) {
        var me = this;
        me.getDetailGrid().setTitle("盘点单明细");
        var grid = me.getMainGrid();
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.getDetailGrid();
        grid.setTitle("单号: " + bill.get("ref")  + " 盘点仓库: "
                + bill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/InvCheck/icBillDetailList",
            params: {
                id: bill.get("id")
            },
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (store.getCount() > 0) {
                        if (id) {
                            var r = store.findExact("id", id);
                            if (r != -1) {
                                grid.getSelectionModel().select(r);
                            }
                        }
                    }
                }

                el.unmask();
            }
        });
    }
});