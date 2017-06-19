// 采购入库 - 主界面
Ext.define("ERP.Purchase.PWMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPPWBill", {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "supplierName", "warehouseName", "inputUserName",
                "bizUserName", "billStatus", "amount", "verifyUserName"]
        });
        var storePWBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPWBill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Purchase/pwbillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storePWBill.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoPWBillGridRecord(me.__lastId);
            }
        });
        //载入前插入查询参数
        storePWBill.on("beforeload", function (s) {
            
            var params = s.getProxy().extraParams;
            var supplier = Ext.getCmp("editSupplier1").getValue();
            //var pwbilldate = Ext.getCmp("editPWBillDate").getValue();
            var beginDate = Ext.getCmp("editBeginDate").getValue();
            var endDate   = Ext.getCmp("editEndDate").getValue();
            var goodsName = Ext.getCmp("editGoodsName").getValue();
            var status    = Ext.getCmp("editStatus").getValue();
            Ext.apply(
                params,{
                    supplier:supplier,
                    goodsname:goodsName,
                    begindate:beginDate,
                    enddate:endDate,
                    status:status
                }
            );
            
        });

        var gridPWBill = Ext.create("Ext.grid.Panel", {
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
                {header: "采购单号", dataIndex: "ref", width: 110, menuDisabled: true, sortable: false},
                {header: "业务日期", dataIndex: "bizDate", menuDisabled: true, sortable: false},
                {header: "供应商", dataIndex: "supplierName", width: 300, menuDisabled: true, sortable: false},
                {header: "采购金额", dataIndex: "amount", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "入库仓库", dataIndex: "warehouseName", menuDisabled: true, sortable: false},
                {header: "业务员", dataIndex: "bizUserName", menuDisabled: true, sortable: false},
                {header: "录单人", dataIndex: "inputUserName", menuDisabled: true, sortable: false},
                {header: "审核人", dataIndex: "verifyUserName", menuDisabled: true, sortable: false},
            ],
            store: storePWBill,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePWBill,
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
                                storePWBill.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                storePWBill.currentPage = 1;
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
                    id: "editSupplier1",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    fieldLabel : "供应商:",
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
                    
                },
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
                    
                },
                {
                    id: "editGoodsName",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    fieldLabel : "商品:",
                    xtype : "jyerp_goodsfield",
                    width : 100
                },
                {
                    xtype : "combo",
                    id : "editStatus",  
                    queryMode : "local",
                    editable : false,   
                    valueField : "id",
                    fieldLabel:'状态', 
                    labelWidth : 30,
                    labelAlign : "right",
                    width:120,
                    store : Ext.create("Ext.data.ArrayStore", {
                        fields : [ "id", "text" ],
                        data : [ [ "", "所有" ], [ "0", "未提交" ], [ "1", "已审核" ], [ "2", "审核中" ],  [ "3", "驳回" ], [ "4", "已入库" ] ]
                    }),
                    value: ''
                },
                {
                    xtype: "container",
                    items: [{
                        xtype: "button",
                        text: "查询",
                        width: 50,
                        iconCls: "ERP-button-refresh",
                        margin: "5, 0, 0, 20",
                        handler: me.onQueryPWbill,
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
                    fn: me.onPWBillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditPWBill,
                    scope: me
                }
            }
        });

        Ext.define("ERPPWBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount",
                "goodsMoney", "goodsPrice", "goodsType", "unitNamePW", "goodsBarCode"]
        });
        var storePWBillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPWBillDetail",
            data: []
        });

        var gridPWBillDetail = Ext.create("Ext.grid.Panel", {
            title: "采购单明细",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false, width: 120},
                {header: "商品条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false, width: 120},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                {header: "采购数量", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right"},
                {header: "单位", dataIndex: "unitNamePW", menuDisabled: true, sortable: false, width: 60},
                {header: "采购单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "采购金额", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "商品种类", dataIndex: "goodsType", menuDisabled: true, sortable: false, width: 100},
            ],
            store: storePWBillDetail,
            listeners: {
                itemdblclick: {
                    fn: me.onEditPWBillDetail,
                    scope: me
                }
            }
        });
        var toolbar = [
                {
                    text: "新建采购单", iconCls: "ERP-button-add", scope: me, handler: me.onAddPWBill
                }, "-", {
                    text: "编辑采购单", iconCls: "ERP-button-edit", scope: me, handler: me.onEditPWBill
                }, "-", {
                    text: "废弃采购单", iconCls: "ERP-button-delete", scope: me, handler: me.onDeletePWBill
                }, "-", {
                    text: "提交审核", iconCls: "ERP-button-commit", scope: me, handler: me.onCommit
                }, "-", {
                    text: "打印", iconCls: "ERP-button-print", scope: me, handler: me.onPrint
                }, "-", {
                    text: "完成", iconCls: "ERP-button-ok", scope: me, handler: me.onFinish
                },"-", {
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
                    items: [gridPWBill]
                }, {
                    region: "center", layout: "fit", border: 0,
                    items: [gridPWBillDetail]
                }]
        });

        me.pwBillGrid = gridPWBill;
        me.pwBillDetailGrid = gridPWBillDetail;

        me.callParent(arguments);

        me.refreshPWBillGrid();
    },
    onQueryPWbill:function(){
        this.refreshPWBillGrid();
    },
    onClearQuery:function(){
        Ext.getCmp("editSupplier1").setValue('');
            //var pwbilldate = Ext.getCmp("editPWBillDate").getValue();
        Ext.getCmp("editBeginDate").setValue('');
        Ext.getCmp("editEndDate").setValue('');
        Ext.getCmp("editGoodsName").setValue('');
        Ext.getCmp("editStatus").setValue('');
        this.refreshPWBillGrid();
    },
    refreshPWBillGrid: function (id) {
        var gridDetail = this.pwBillDetailGrid;
        gridDetail.setTitle("采购入库单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    onAddPWBill: function () {
        var form = Ext.create("ERP.Purchase.PWEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditPWBill: function () {
        var item = this.pwBillGrid.getSelectionModel().getSelection();

        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要编辑的采购单");
            return;
        }
        var pwBill = item[0];
        //判断采购订单的状态，看是否可以编辑
        if(pwBill.get("billStatus") == "审核中"){
            ERP.MsgBox.showInfo("采购单正在审核中，无法编辑");
            return;
        }
        if(pwBill.get("billStatus") == "已审核"){
            ERP.MsgBox.showInfo("采购单已经审核通过了，无法编辑");
            return;
        }
        if(pwBill.get("billStatus") == "已入库"){
            ERP.MsgBox.showInfo("采购单已经入库，无法编辑");
            return;
        }
        var form = Ext.create("ERP.Purchase.PWEditForm", {
            parentForm: this,
            entity: pwBill
        });
        form.show();
    },
    onFinish: function(){
        var me = this;
        var item = me.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要完成的采购单");
            return;
        }
        
        var pwBill = item[0];
        var store = me.pwBillGrid.getStore();
        var index = store.findExact("id", pwBill.get("id"));
        var info = "请确认是否完成采购单: <span style='color:red'>" + pwBill.get("ref") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在完成操作...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/finishPWBill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();
                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成操作", function () {
                                me.refreshPWBillGrid(pwBill.get("id"));
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
    onDeletePWBill: function () {
        var me = this;
        var item = me.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的采购入库单");
            return;
        }
        
        var pwBill = item[0];
        var store = me.pwBillGrid.getStore();
        var index = store.findExact("id", pwBill.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        var info = "请确认是否废弃采购单: <span style='color:red'>" + pwBill.get("ref") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/deletePWBill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成废弃操作", function () {
                                me.refreshPWBillGrid(preIndex);
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
    onPWBillGridSelect: function () {
        this.refreshPWBillDetailGrid();
    },
    refreshPWBillDetailGrid: function (id) {
        var me = this;
        me.pwBillDetailGrid.setTitle("采购单明细");
        var grid = me.pwBillGrid;
        var item = me.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var pwBill = item[0];

        var grid = me.pwBillDetailGrid;
        grid.setTitle("单号: " + pwBill.get("ref") + " 供应商: " + pwBill.get("supplierName") + " 入库仓库: " + pwBill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Purchase/pwBillDetailList",
            params: {pwBillId: pwBill.get("id")},
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
    onCommit: function () {
        var item = this.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的采购单");
            return;
        }
        var pwBill = item[0];

        var detailCount = this.pwBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前采购单没有录入商品明细，不能提交");
            return;
        }
        //判断是否已经审核过了
        var billStatus = pwBill.get("billStatus");
        if(billStatus == "已入库" || billStatus == "已审核"){
            ERP.MsgBox.showInfo("当前订单状态不正确");
            return;
        }
        var info = "请确认是否提交单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的采购单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/commitPWBill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshPWBillGrid();
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
    
    gotoPWBillGridRecord: function (id) {
        var me = this;
        var grid = me.pwBillGrid;
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

    onPrint:function(){
        var item = this.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要打印的采购单");
            return;
        }
        var pwBill = item[0];

        var detailCount = this.pwBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前采购单没有录入商品明细，不能打印");
            return;
        }
        var ref = (pwBill.get("ref"));
        var url = location.protocol+"//"+location.hostname + "/Home/Print/pwBillInfo?ref="+ref;
        LODOP=getLodop();  
        LODOP.PRINT_INIT("打印采购订单");
        LODOP.ADD_PRINT_URL(30,20,746,"95%",url);
        LODOP.SET_PRINT_STYLEA(0,"HOrient",3);
        LODOP.SET_PRINT_STYLEA(0,"VOrient",3);
        LODOP.PREVIEW();
    }
});