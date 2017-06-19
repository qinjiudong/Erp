// 采购退货入库 - 主界面
Ext.define("ERP.Sale.SRMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [{
                    text: "新建退换货单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddSRBill
                },  "-", {
                    text: "编辑",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onEditSRBill
                }, "-", {
                    text: "删除",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteSRBill
                }, "-", {
                    text: "提交并退货入库",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: function(){me.onCommit(1)}
                }, "-", {
                    text: "关闭",
                    iconCls: "ERP-button-exit",
                    handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }, "-", {
                    text: "导出",
                    iconCls: "ERP-button-exit",
                    handler: function () {
                       	send_parm = '&page=1&start=0&limit=2000&reason='+Ext.getCmp("search_reason").getValue()+"&begin="+Ext.Date.format(Ext.getCmp("search_begin").getValue(), "Y-m-d")+"&end="+Ext.Date.format(Ext.getCmp("search_end").getValue(), "Y-m-d")+"&SR_ID="+Ext.getCmp("SR_ID").getValue();

						url = ERP.Const.BASE_URL + "Home/Sale/srbillList?act=export" + send_parm;
						window.open(url);
                    }
                }],
            items: [{
                    region: "north",
                    height: "30%",
                    split: true,
                    layout: "fit",
                    border: 0,
                    items: [me.getSRGrid()]
                }, {
                    region: "center",
                    layout: "fit",
                    border: 0,
                    items: [me.getSRDetailGrid()]
                }]
        });

        me.callParent(arguments);

        me.refreshSRBillGrid();
    },
    refreshSRBillGrid: function (id) {
        var gridDetail = this.getSRDetailGrid();
        gridDetail.setTitle("退换货入库单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    
    // 新增退换货入库单
    onAddSRBill: function () {
        var form = Ext.create("ERP.Sale.SREditForm", {
            parentForm: this
        });
        form.show();
    },
    
    // 编辑退换货入库单
    onEditSRBill: function () {
    	var me = this;
        var item = me.getSRGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的退换货入库单");
            return;
        }
        var bill = item[0];

        var form = Ext.create("ERP.Sale.SREditForm", {
            parentForm: me,
            entity: bill
        });
        form.show();
    },
    
    // 删除退换货入库单
    onDeleteSRBill: function () {
    	var me = this;
        var item = me.getSRGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的退换货入库单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否删除退换货入库单: <span style='color:red'>" + bill.get("ref")
                + "</span>";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/deleteSRBill",
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
                                me.refreshSRBillGrid();
                            });
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
    
    // 提交退换货入库单
    onCommit: function (in_warehouse) {
        var me = this;
        var item = me.getSRGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的退换货入库单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的退换货入库单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/commitSRBill",
                method: "POST",
                params: {id: bill.get("id"), in_warehouse:in_warehouse},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshSRBillGrid(data.id);
                            });
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
    
    getSRGrid: function () {
        var me = this;
        if (me.__srGrid) {
            return me.__srGrid;
        }

        var modelName = "ERPSRBill";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "customerName", "warehouseName",
                "inputUserName", "bizUserName", "billStatus", "amount", "order_ref","tel","sitename","reason"]
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
                url: ERP.Const.BASE_URL + "Home/Sale/srbillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });

        store.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoSRBillGridRecord(me.__lastId);
            }
        });

        store.on("beforeload", function () {
            var reason = Ext.getCmp("search_reason").getValue();
            var SR_ID = Ext.getCmp("SR_ID").getValue();
            var begin = Ext.Date.format(Ext.getCmp("search_begin").getValue(), "Y-m-d");
            var end = Ext.Date.format(Ext.getCmp("search_end").getValue(), "Y-m-d");
            var r = {};
            if(reason){
                r.reason = reason;
            }
            if(SR_ID){
                r.SR_ID = SR_ID;
            }
            if(begin){
                r.begin = begin;
            }
            if(end){
                r.end = end;
            }
            store.proxy.extraParams = r;
        });

        me.__srGrid = Ext.create("Ext.grid.Panel", {
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
                    	return value == "待入库" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                }, {
                    header: "单号",
                    dataIndex: "ref",
                    width: 110,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "关联订单号",
                    dataIndex: "order_ref",
                    width: 110,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "业务日期",
                    dataIndex: "bizDate",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "客户",
                    dataIndex: "customerName",
                    width: 200,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "退货金额",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 80
                }, {
                    header: "入库仓库",
                    dataIndex: "warehouseName",
                    menuDisabled: true,
                    sortable: false
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
                },{
                    header: "退货原因",
                    dataIndex: "reason",
                    menuDisabled: true,
                    sortable: false
                }],
            listeners: {
                select: {
                    fn: me.onSRBillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditSRBill,
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
                },
                {
                            xtype : "combo",
                            id : "search_reason",  
                            //queryMode : "local",
                            editable : false,   
                            valueField : "id",
                            allowBlank: false,
                            fieldLabel:'退货原因', 
                            labelWidth : 60,
                            labelAlign : "right",
                            width:150,
                            store : Ext.create("Ext.data.ArrayStore", {
                                fields : [ "id","text" ],
                                data : [ [ "", "" ], [ "缺货", "缺货" ], [ "质量问题", "质量问题" ],  
                                [ "测试", "测试" ], ["撤单", "撤单"]]
                            }),
                            value: ''
                }, 
                {
                    id: "search_begin",
                    xtype: "datefield",
                    format: "Y-m-d",
                    labelAlign: "left",
                    labelSeparator: "",
                    width : 170,
                    fieldLabel: "下单日期",
                    labelWidth:50,
                    value:""
                },
                {
                    id: "search_end",  
                    xtype: "datefield",
                    format: "Y-m-d",labelAlign: "left",
                    labelSeparator: "",
                    labelWidth : 8,
                    width :130,
                    fieldLabel: "到",
                    value:Ext.Date.format(new Date(), 'Y-m-d')
                }, 
                {
                    id: "SR_ID",  
                    xtype: "textfield",
                    labelAlign: "left",
                    labelSeparator: "",
                    labelWidth : 8,
                    width :130,
                    fieldLabel: "单号"
                }, 
                ]
        });

        return me.__srGrid;
    },
    getSRDetailGrid: function() {
        var me = this;
        if (me.__srDetailGrid) {
            return me.__srDetailGrid;
        }
        
        var modelName = "ERPSRBillDetail";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName",
                "goodsCount", "goodsMoney", "goodsPrice", "rejCount", "rejPrice", "rejSaleMoney", "rejActualCount"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: []
        });

        me.__srDetailGrid = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            title: "退换货入库单明细",
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
                    width: 100
                }, {
                    header: "退货数量",
                    dataIndex: "rejCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right"
                }, 
                {
                    header: "实际入库数量",
                    dataIndex: "rejActualCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right"
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
                    xtype: "numbercolumn",
                    width: 100
                }, {
                    header: "退货金额",
                    dataIndex: "rejSaleMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                }],
            store: store
        });

        return me.__srDetailGrid;
    },
    gotoSRBillGridRecord: function (id) {
        var me = this;
        var grid = me.getSRGrid();
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
    onSRBillGridSelect: function() {
    	this.freshSRBillDetailGrid();
    },
    freshSRBillDetailGrid: function(id) {
        var me = this;
        me.getSRDetailGrid().setTitle("退换货入库单明细");
        var grid = me.getSRGrid();
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.getSRDetailGrid();
        grid.setTitle("单号: " + bill.get("ref") + " 客户: "
                + bill.get("customerName") + " 入库仓库: "
                + bill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/srBillDetailList",
            params: {
                billId: bill.get("id")
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