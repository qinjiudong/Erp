// 采购退货出库 - 主界面
Ext.define("ERP.PurchaseRej.PRMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [{
                    text: "新建采购退货出库单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddBill
                }, "-", {
                    text: "编辑采购退货出库单",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onEditBill
                }, "-", {
                    text: "废弃采购退货出库单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteBill
                }, "-", {
                    text: "提交采购退货出库单",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: me.onCommit
                }, "-",
                {
                    id : "editBeginDate",
                    fieldLabel : "日期",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    //allowBlank : false,
                    //blankText : "没有输入业务日期",
                    //beforeLabelTextTpl : ERP.Const.REQUIRED,
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "PWBeginBillDate",
                    width:130
                    
                }, "-",
                {
                    id : "editEndDate",
                    fieldLabel : "-",
                    labelWidth : 5,
                    labelAlign : "right",
                    labelSeparator : "",
                    //allowBlank : false,
                    //blankText : "没有输入业务日期",
                    //beforeLabelTextTpl : ERP.Const.REQUIRED,
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "PWEndBillDate",
                    width:105
                    
                }, "-",
				{
				xtype: "displayfield",
				value: "验收单"
				},{
					xtype: "textfield",
					width:70,
					id: "ysRefNumber",
				},{
				xtype: "displayfield",
				value: "退货单"
				},{
					width:70,
					xtype: "textfield",
					id: "thRefNumber",
				},{
				xtype: "displayfield",
				value: "供应商"
				},{
					width:60,
					xtype: "textfield",
					id: "editSupplierName",
				},
                {
                    xtype: "container",
                    items: [{
                        xtype: "button",
                        text: "查询",
                        width: 50,
                        iconCls: "ERP-button-refresh",
                        margin: "5, 0, 0, 20",
                        handler: me.onQueryPRbill,
                        scope: me
                    }]
                }, "-",
                {
                    text: "导出",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: me.onExport
                }, "-", {
                    text: "关闭",
                    iconCls: "ERP-button-exit",
                    handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }],
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
        gridDetail.setTitle("采购退货出库单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        me.__lastId = id;
    },
    
    // 新增采购退货出库单
    onAddBill: function () {
    	var me = this;
    	var form = Ext.create("ERP.PurchaseRej.PREditForm", {
    		parentForm: me
    	});
    	form.show();
    },
    onQueryPRbill:function(){
        this.refreshMainGrid();
    },
    
    // 编辑采购退货出库单
    onEditBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的采购退货出库单");
            return;
        }
        var bill = item[0];
    	var form = Ext.create("ERP.PurchaseRej.PREditForm", {
    		parentForm: me,
    		entity: bill
    	});
    	form.show();
    },
    
    // 删除采购退货出库单
    onDeleteBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要废弃的采购退货出库单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否废弃采购退货出库单: <span style='color:red'>" + bill.get("ref")
                + "</span>";
        
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/PurchaseRej/deletePRBill",
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
    
    // 提交采购退货出库单
    onCommit: function () {
        var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的采购退货出库单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的采购退货出库单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/PurchaseRej/commitPRBill",
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
            fields: ["id", "ref","ysref", "bizDT",  "warehouseName", "supplierName",
                "inputUserName", "caiwuqueren", "bizUserName", "billStatus", "rejMoney","rejTax", "rejTaxMoney", "rejNoTaxMoney"]
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
                url: ERP.Const.BASE_URL + "Home/PurchaseRej/prbillList",
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
        //载入前插入查询参数
        store.on("beforeload", function (s) {
            var params = s.getProxy().extraParams;
            var beginDate = Ext.getCmp("editBeginDate").getValue();
            var endDate   = Ext.getCmp("editEndDate").getValue();
			var ysRef   = Ext.getCmp("ysRefNumber").getValue();
			var thRef   = Ext.getCmp("thRefNumber").getValue();
			var spplier   = Ext.getCmp("editSupplierName").getValue();
            Ext.apply(
                params,{
                    begindate:beginDate,
                    enddate:endDate,
					ysRef:ysRef,
					thRef:thRef,
					spplier:spplier,
                }
            );
            
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
                    	return value == "待出库" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                }, {
                    header: "单号",
                    dataIndex: "ref",
                    width: 110,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "验收单号",
                    dataIndex: "ysref",
                    width: 125,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "业务日期",
                    dataIndex: "bizDT",
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "供应商",
                    dataIndex: "supplierName",
                    menuDisabled: true,
                    sortable: false,
                    width: 150
                }, {
                    header: "出库仓库",
                    dataIndex: "warehouseName",
                    menuDisabled: true,
                    sortable: false,
                    width: 150
                }, {
                	header: "退货金额", 
                	dataIndex: "rejMoney", 
                	menuDisabled: true, 
                	sortable: false, 
                	align: "right", 
                	xtype: "numbercolumn", 
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
                }, {
                    header: "财务确认",
                    dataIndex: "caiwuqueren",
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
                }, {
                    text: "财务确认", iconCls: "ERP-button-commit", scope: me, handler: me.onQueren
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
                     "rejCount", "rejPrice", "rejMoney","rejTax", "rejTaxMoney", "rejNoTaxMoney"]
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
            title: "采购退货出库单明细",
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
                    header: "规格型号",
                    dataIndex: "goodsSpec",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                }, {
                    header: "退货数量",
                    dataIndex: "rejCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150
                }, {
                    header: "单位",
                    dataIndex: "unitName",
                    menuDisabled: true,
                    sortable: false,
                    width: 60
                }, {
                    header: "退货单价",
                    dataIndex: "rejPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150,
                    xtype: "numbercolumn"
                },  {
                    header: "退货金额(含税)",
                    dataIndex: "rejMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150,
                    xtype: "numbercolumn"
                },  {
                    header: "税率",
                    dataIndex: "rejTax",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150,
                    xtype: "numbercolumn"
                },{
                    header: "退货金额(税)",
                    dataIndex: "rejTaxMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150,
                    xtype: "numbercolumn"
                }, {
                    header: "退货金额(无税)",
                    dataIndex: "rejNoTaxMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 150,
                    xtype: "numbercolumn"
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
        me.getDetailGrid().setTitle("采购退货出库单明细");
        var grid = me.getMainGrid();
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.getDetailGrid();
        grid.setTitle("单号: " + bill.get("ref")  + " 出库仓库: "
                + bill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/PurchaseRej/prBillDetailList",
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
    onQueren: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要确认的采购退货出库单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否确认采购退货出库单: <span style='color:red'>" + bill.get("ref")
                + "</span>";
        
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在确认中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/PurchaseRej/querenPRBill",
                timeout:300000,
                method: "POST",
                params: {
                    id: bill.get("id")
                },
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成确认操作", function () {
                                me.refreshMainGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                            window.location.reload();
                    }
                }

            });
        });
    },
    onExport: function(){
            var beginDate = Ext.getCmp("editBeginDate").getValue();
            var endDate   = Ext.getCmp("editEndDate").getValue();
        var timestamp2 = Date.parse(beginDate.toISOString().slice(0,10));
        timestamp2 = timestamp2 / 1000;
        var newDate = new Date();
        newDate.setTime((timestamp2+24*3600) * 1000);
        beginDate = newDate.toISOString().slice(0,10);
        var timestamp1 = Date.parse(endDate.toISOString().slice(0,10));
        timestamp1 = timestamp1 / 1000;
        newDate.setTime((timestamp1+24*3600) * 1000);
        endDate = newDate.toISOString().slice(0,10);
        url = ERP.Const.BASE_URL + "Home/PurchaseRej/prbillList?act=export&start=0&limit=10000"+"&begindate="+beginDate+"&enddate="+endDate;
        window.open(url);
    }
});