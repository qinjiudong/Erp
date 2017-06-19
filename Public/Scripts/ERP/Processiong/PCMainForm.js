// 加工入库 - 主界面
Ext.define("ERP.Purchase.PCMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPPCBill", {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "goodsCode", "goodsName", "goodsCount","goodsUnit",
                "bizUserName", "billStatus", "amount","inWarehouseName", "outWarehouseName", "inputUserName"]
        });
        var storePCBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPCBill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Processiong/pcBillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storePCBill.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoPCBillGridRecord(me.__lastId);
            }
        });
        //载入前插入查询参数
        storePCBill.on("beforeload", function (s) {
            var params = s.getProxy().extraParams;
            var pcbillid = Ext.getCmp("editPCID").getValue();
            var pcbilldate = Ext.getCmp("editPCBillDate").getValue();
            Ext.apply(params,{pcbillid:pcbillid,pcbilldate:pcbilldate});    
        });

        var gridPCBill = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                {header: "状态", dataIndex: "billStatus", menuDisabled: true, sortable: false, width: 60,
                	renderer: function (value) {
                        var ret = "";
                        if(value == "未提交"){
                            ret = "<span style='color:#aaa'>" + value + "</span>";
                        } else if (value == "已审核"){
                            ret = "<span style='color:green'>" + value + "</span>";
                        } else if (value == "已驳回"){
                            ret = "<span style='color:red'>" + value + "</span>";
                        } else if (value == "已入库"){
                            ret = "<span style='color:#000'>" + value + "</span>";
                        } else if (value == "审核中"){
                            ret = "<span style='color:#000'>" + value + "</span>";
                        }
                        return ret;
                        //return value == "待入库" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                },
                {header: "加工单号", dataIndex: "ref", width: 110, menuDisabled: true, sortable: false},
                {header: "业务日期", dataIndex: "bizDate", menuDisabled: true, sortable: false},
                /*
                {header: "成品编号", dataIndex: "goodsCode", width: 150, menuDisabled: true, sortable: false},
                {header: "成品名称", dataIndex: "goodsName", width: 300, menuDisabled: true, sortable: false},
                {header: "成品数量", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 60},
                {header: "成品单位", dataIndex: "goodsUnit", menuDisabled: true, sortable: false,width: 60},
                */
                {header: "出仓", dataIndex: "outWarehouseName", menuDisabled: true, sortable: false},
                {header: "入仓", dataIndex: "inWarehouseName", menuDisabled: true, sortable: false},
                {header: "业务员", dataIndex: "bizUserName", menuDisabled: true, sortable: false},
                {header: "录单人", dataIndex: "inputUserName", menuDisabled: true, sortable: false}
            ],
            store: storePCBill,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePCBill,
                    listeners:{
                        "beforechange" : function(bbar, params){
                            //Ext.apply(params,{catalogid:123});  
                            //return false;  
                        }
                    }
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
                                storePCBill.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                storePCBill.currentPage = 1;
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
                    id: "editPCID",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    fieldLabel : "加工单号:",
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
                    id : "editPCBillDate",
                    fieldLabel : "日期",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    //allowBlank : false,
                    //blankText : "没有输入业务日期",
                    //beforeLabelTextTpl : ERP.Const.REQUIRED,
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "PCBillDate",
                    
                },
                {
                    xtype: "container",
                    items: [{
                        xtype: "button",
                        text: "查询",
                        width: 50,
                        iconCls: "ERP-button-refresh",
                        margin: "5, 0, 0, 20",
                        handler: me.onQueryPCbill,
                        scope: me
                    },{
                        xtype: "button",
                        text: "清空查询条件",
                        width: 100,
                        iconCls: "ERP-button-cancel",
                        margin: "5, 0, 0, 5",
                        handler: me.onClearQuery,
                        scope: me
                    }]
                }

                ],
            listeners: {
                select: {
                    fn: me.onPCBillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditPCBill,
                    scope: me
                }
            }
        });

        Ext.define("ERPPCBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName", "goodsSpec",
                    "unitName", "goodsCount", "goodsMoney", "goodsPrice", "goodsType", "goodsCount", "goodsCountAfter", "goodsCountAfterActual"]
        });
        var storePCBillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPCBillDetail",
            data: []
        });

        Ext.define("ERPPCBillDetail2", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsBarCode", "goodsCode", "goodsName", "goodsSpec",
                    "unitName", "goodsCount", "goodsMoney", "goodsPrice", "goodsType"]
        });
        var storePCBillDetail2 = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPCBillDetail2",
            data: []
        });

        var gridPCBillDetail = Ext.create("Ext.grid.Panel", {
            title: "半成品明细",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false, width: 120},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "加工前库存", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right"},
                {header: "加工后库存", dataIndex: "goodsCountAfter", menuDisabled: true, sortable: false, align: "right"},
                {header: "加工后实际库存", dataIndex: "goodsCountAfterActual", menuDisabled: true, sortable: false, align: "right"},
                //{header: "加工前库存", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right"},
                //{header: "采购单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                //{header: "采购金额", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                //{header: "商品种类", dataIndex: "goodsType", menuDisabled: true, sortable: false, width: 100},
            ],
            store: storePCBillDetail,
            listeners: {
                itemdblclick: {
                    fn: me.onEditPCBillDetail,
                    scope: me
                }
            }
        });

        var gridPCBillDetail2 = Ext.create("Ext.grid.Panel", {
            title: "成品明细",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "散装条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false, width: 150},
                //{header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false, width: 120},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                {header: "数量", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right"},
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                //{header: "采购单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                //{header: "采购金额", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                //{header: "商品种类", dataIndex: "goodsType", menuDisabled: true, sortable: false, width: 100},
            ],
            store: storePCBillDetail2,
            listeners: {
                itemdblclick: {
                    fn: me.onEditPCBillDetail,
                    scope: me
                }
            }
        });

        var toolbar = [
                {
                    text: "新建加工单", iconCls: "ERP-button-add", scope: me, handler: me.onAddPCBill
                }, "-", {
                    text: "编辑加工单", iconCls: "ERP-button-edit", scope: me, handler: me.onEditPCBill
                }, "-", {
                    text: "废弃加工单", iconCls: "ERP-button-delete", scope: me, handler: me.onDeletePCBill
                }, "-", {
                    text: "提交审核", iconCls: "ERP-button-commit", scope: me, handler: me.onCommit
                }, "-", {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ];
        Ext.apply(me, {
            tbar: toolbar,
            items: [{
                    region: "north", height: "30%",
                    split: true, layout: "fit", border: 0,
                    items: [gridPCBill]
                }, {
                    region: "center", layout: "fit", border: 0,height:100,
                    items: [gridPCBillDetail]
                },{
                    region: "south", layout: "fit", border: 0,height:200,
                    items: [gridPCBillDetail2]
                }

                ]
        });

        me.pcBillGrid = gridPCBill;
        me.pcBillDetailGrid = gridPCBillDetail;
        me.pcBillDetailGrid2 = gridPCBillDetail2;

        me.callParent(arguments);

        me.refreshPCBillGrid();
    },
    onQueryPCbill:function(){
        this.refreshPCBillGrid();
    },
    onClearQuery:function(){
        Ext.getCmp("editPCID").setValue("");
        Ext.getCmp("editPCBillDate").setValue("");
        this.refreshPCBillGrid();
    },
    refreshPCBillGrid: function (id) {
        var gridDetail = this.pcBillDetailGrid;
        gridDetail.setTitle("半成品明细");
        gridDetail.getStore().removeAll();
        var gridDetail2 = this.pcBillDetailGrid2;
        gridDetail2.setTitle("成品明细");
        gridDetail2.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    onAddPCBill: function () {
        var form = Ext.create("ERP.Purchase.PCEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditPCBill: function () {
        var item = this.pcBillGrid.getSelectionModel().getSelection();

        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要编辑的加工单");
            return;
        }
        var pcBill = item[0];
        //判断采购订单的状态，看是否可以编辑
        if(pcBill.get("billStatus") == "审核中"){
            ERP.MsgBox.showInfo("加工单正在审核中，无法编辑");
            return;
        }
        if(pcBill.get("billStatus") == "已审核"){
            ERP.MsgBox.showInfo("加工单已经审核通过了，无法编辑");
            return;
        }
        if(pcBill.get("billStatus") == "已完成"){
            ERP.MsgBox.showInfo("加工单已经入库，无法编辑");
            return;
        }
        var form = Ext.create("ERP.Purchase.PCEditForm", {
            parentForm: this,
            entity: pcBill
        });
        form.show();
    },
    onDeletePCBill: function () {
        var me = this;
        var item = me.pcBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要废弃的加工单");
            return;
        }
        
        var pcBill = item[0];
        //判断加工单的状态，看是否可以编辑
        if(pcBill.get("billStatus") == "审核中"){
            ERP.MsgBox.showInfo("加工单正在审核中，无法废弃");
            return;
        }
        if(pcBill.get("billStatus") == "已审核"){
            ERP.MsgBox.showInfo("加工单已经审核通过了，无法废弃");
            return;
        }
        if(pcBill.get("billStatus") == "已完成"){
            ERP.MsgBox.showInfo("加工单已经入库，无法废弃");
            return;
        }
        var store = me.pcBillGrid.getStore();
        var index = store.findExact("id", pcBill.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        var info = "请确认是否废弃加工单: <span style='color:red'>" + pcBill.get("ref") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在废弃中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Processiong/deletePCBill",
                method: "POST",
                params: {id: pcBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成废弃操作", function () {
                                me.refreshPCBillGrid(preIndex);
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
    onPCBillGridSelect: function () {
        this.refreshPCBillDetailGrid();
    },
    refreshPCBillDetailGrid: function (id) {
        var me = this;
        me.pcBillDetailGrid.setTitle("半成品明细");
        var grid = me.pcBillGrid;
        var item = me.pcBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var pcBill = item[0];

        var grid = me.pcBillDetailGrid;
        var grid2 = me.pcBillDetailGrid2;
        //grid.setTitle("单号: " + pwBill.get("ref") + " 供应商: " + pwBill.get("supplierName") + " 入库仓库: " + pwBill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Processiong/pcBillDetailList",
            params: {pcBillId: pcBill.get("id")},
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();
                var store2 = grid2.getStore();

                store.removeAll();
                store2.removeAll();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data.data1);
                    store2.add(data.data2)
                    if (store.getCount() > 0) {
                        if (id) {
                            var r = store.findExact("id", id);
                            if (r != -1) {
                                grid.getSelectionModel().select(r);
                            }
                        }
                    }
                    if (store2.getCount() > 0) {
                        if (id) {
                            var r = store2.findExact("id", id);
                            if (r != -1) {
                                grid2.getSelectionModel().select(r);
                            }
                        }
                    }
                }

                el.unmask();
            }
        });
    },
    onCommit: function () {
        var item = this.pcBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的加工单");
            return;
        }
        var pcBill = item[0];

        var detailCount = this.pcBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前加工单没有录入半成品明细，不能提交");
            return;
        }
        var detailCount2 = this.pcBillDetailGrid2.getStore().getCount();
        if (detailCount2 == 0) {
            ERP.MsgBox.showInfo("当前加工单没有录入成品明细，不能提交");
            return;
        }


        var info = "请确认是否提交单号: <span style='color:red'>" + pcBill.get("ref") + "</span> 的加工单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Processiong/submitPCBill",
                method: "POST",
                params: {id: pcBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshPCBillGrid();
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
    
    gotoPCBillGridRecord: function (id) {
        var me = this;
        var grid = me.pcBillGrid;
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
    }
});