// 验收入库 - 主界面
Ext.define("ERP.Purchase.IAMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPIABill", {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "supplierName", "supplierCode", "warehouseName", "inputUserName", "caiwuqueren", "caiwushoukuan",
                "bizUserName", "billStatus", "amount"]
        });
        var storeIABill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPIABill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/InvAcceptance/iabillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storeIABill.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoIABillGridRecord(me.__lastId);
            }
        });
        //载入前插入查询参数
        storeIABill.on("beforeload", function (s) {
            var params = s.getProxy().extraParams;
            var iabillid = Ext.getCmp("editIAID").getValue();
            var result = {
                "iabillid" : iabillid
            };
            
            if(Ext.getCmp("editBeginDate").getValue()){
                var begindate = Ext.Date.format(Ext.getCmp("editBeginDate").getValue(), 'Y-m-d');
                result.begindate = begindate;
            }
            if(Ext.getCmp("editEndDate").getValue()){
                var enddate = Ext.Date.format(Ext.getCmp("editEndDate").getValue(), 'Y-m-d');
                result.enddate = enddate;
            }
            if(Ext.getCmp("editSearchSupplier").getValue()){
                var supplier = Ext.getCmp("editSearchSupplier").getValue();
                result.supplier = supplier;
            }
            if(Ext.getCmp("editSearchGoodsCode").getValue()){
                var goods_code = Ext.getCmp("editSearchGoodsCode").getValue();
                result.goods_code = goods_code;
            }
            if(Ext.getCmp("auto")){
                var auto = Ext.getCmp("auto").getValue();
                result.auto = auto;
            }
            if(Ext.getCmp("editStatus")){
                var editStatus = Ext.getCmp("editStatus").getValue();
                result.editStatus = editStatus;
            }



            
            Ext.apply(params,result);    
        });
        //载入前插入查询参数
        /*
        storeIABill.on("beforeload", function (s) {
            var params = s.getProxy().extraParams;
            var pwbillid = Ext.getCmp("editIAID").getValue();
            var pwbilldate = Ext.getCmp("editIABillDate").getValue();
            Ext.apply(params,{pwbillid:pwbillid,pwbilldate:pwbilldate});    
        });
        */
        var tbar = [
                {
                    id: "editIAID",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    fieldLabel : "验收单号:",
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
                    labelWidth : 40,
                    labelAlign : "right",
                    labelSeparator : "",
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "beginDate",
                    width:150
                },"-",
                {
                    id : "editEndDate",
                    fieldLabel : "-",
                    labelWidth : 10,
                    labelAlign : "right",
                    labelSeparator : "",
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "endDate",
                    width:120
                },{
                    id : "editSearchSupplier",
                    fieldLabel : "供应商",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    xtype : "textfield",
                    value : '',//new Date(),
                    name : "supplier",
                },
                {
                        id : "editSearchGoodsCode",
                        fieldLabel : "商品编码",
                        labelWidth : 60,
                        labelAlign : "right",
                        labelSeparator : "",
                        xtype : "textfield",
                        value : '',//new Date(),
                        name : "goodsCode",
                },
                {
                    xtype : "combo",
                    id : "auto",  
                    queryMode : "local",
                    editable : false,   
                    valueField : "id",
                    fieldLabel:'供应商属性', 
                    labelWidth : 60,
                    labelAlign : "right",
                    width:120,
                    store : Ext.create("Ext.data.ArrayStore", {
                        fields : [ "id", "text" ],
                        data : [ [ "", "所有" ], [ "0", "经销" ], [ "1", "联营" ] ]
                    }),
                    value: '0'
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
                        data : [ [ "", "所有" ], [ "0", "审核中" ], [ "1", "已入库" ] ]
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
                        handler: me.onQueryIAbill,
                        scope: me
                    },
                    /*
                    {
                        xtype: "button",
                        text: "清空查询条件",
                        width: 100,
                        iconCls: "ERP-button-cancel",
                        margin: "5, 0, 0, 5",
                        handler: me.onClearQuery,
                        scope: me
                    },
                    */
                    {
                        xtype: "button",
                        text: "导出",
                        width: 100,
                        iconCls: "ERP-button-cancel",
                        margin: "5, 0, 0, 5",
                        handler: me.onExport,
                        scope: me
                    },
                    {
                        xtype: "button",
                        text: "导出明细",
                        width: 100,
                        iconCls: "ERP-button-cancel",
                        margin: "5, 0, 0, 5",
                        handler: me.onExportDetail,
                        scope: me
                    }
                    ]
                }

                ];

        var gridIABill = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                {header: "状态", dataIndex: "billStatus", menuDisabled: true, sortable: false, width: 60,
                	renderer: function (value) {
                        var ret = "";
                        if(value == "已入库"){
                            ret = "<span style='color:green'>" + value + "</span>";
                        } 
                        else if(value == "审核中") {
                            ret = "<span style='color:red'>" + value + "</span>";
                        }
                        return ret;
                        //return value == "待入库" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                },
                {header: "验收单号", dataIndex: "ref", width: 110, menuDisabled: true, sortable: false},
                {header: "入库日期", dataIndex: "bizDate", menuDisabled: true, sortable: false},
                {header: "供应商", dataIndex: "supplierName", width: 300, menuDisabled: true, sortable: false},
                {header: "供应商编码", dataIndex: "supplierCode", width: 100, menuDisabled: true, sortable: false},
                {header: "入库金额", dataIndex: "amount", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "入库仓库", dataIndex: "warehouseName", menuDisabled: true, sortable: false},
                {header: "业务员", dataIndex: "bizUserName", menuDisabled: true, sortable: false},
                {header: "录单人", dataIndex: "inputUserName", menuDisabled: true, sortable: false},
                {header: "财务确认", dataIndex: "caiwuqueren", menuDisabled: true, sortable: false},
                {header: "财务付款", dataIndex: "caiwushoukuan", menuDisabled: true, sortable: false}
            ],
            store: storeIABill,
            tbar: tbar,
            listeners: {
                select: {
                    fn: me.onIABillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditIABill,
                    scope: me
                }
            }
        });

        Ext.define("ERPIABillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsBarCode", "goodsName", "goodsSpec", "unitName", "goodsCount",
                "goodsMoney","goodsTax","goodsTaxMoney","goodsNoTaxMoney", "goodsPrice", "goodsType"]
        });
        var storeIABillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPIABillDetail",
            data: []
        });

        var gridIABillDetail = Ext.create("Ext.grid.Panel", {
            title: "验收单明细",
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
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "采购单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "采购金额(含税)", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "税率", dataIndex: "goodsTax", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "采购税额", dataIndex: "goodsTaxMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "采购金额(无税)", dataIndex: "goodsNoTaxMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "商品种类", dataIndex: "goodsType", menuDisabled: true, sortable: false, width: 100},
            ],
            store: storeIABillDetail,
            listeners: {
                itemdblclick: {
                    fn: me.onEditIABillDetail,
                    scope: me
                }
            }
        });
        var toolbar = [
                {
                    text: "新建验收单", iconCls: "ERP-button-add", scope: me, handler: me.onAddIABill
                }, "-", {
                    text: "提交审核", iconCls: "ERP-button-commit", scope: me, handler: me.onCommit
                },
                /*
                "-", {
                    text: "编辑采购单", iconCls: "ERP-button-edit", scope: me, handler: me.onEditPWBill
                }, "-", {
                    text: "废弃采购单", iconCls: "ERP-button-delete", scope: me, handler: me.onDeletePWBill
                }, "-", {
                    text: "提交审核", iconCls: "ERP-button-commit", scope: me, handler: me.onCommit
                }, 
                */

                "-", {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                },
                {
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storeIABill,
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
                                storeIABill.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                storeIABill.currentPage = 1;
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
                }, "-", {
                    text: "财务付款", iconCls: "ERP-button-commit", scope: me, handler: me.onShoukuan
                },
            ];
        Ext.apply(me, {
            tbar: toolbar,
            items: [{
                    region: "north", height: "30%",
                    split: true, layout: "fit", border: 0,
                    items: [gridIABill]
                }, {
                    region: "center", layout: "fit", border: 0,
                    items: [gridIABillDetail]
                }]
        });

        me.iaBillGrid = gridIABill;
        me.iaBillDetailGrid = gridIABillDetail;

        me.callParent(arguments);

        me.refreshIABillGrid();
    },
    onQueryIAbill:function(){
        this.refreshIABillGrid();
    },
    onClearQuery:function(){
        Ext.getCmp("editIAID").setValue("");
        Ext.getCmp("editIABillDate").setValue("");
        this.refreshIABillGrid();
    },
    refreshIABillGrid: function (id) {
        var gridDetail = this.iaBillDetailGrid;
        gridDetail.setTitle("验收单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    onAddIABill: function () {
        var form = Ext.create("ERP.Purchase.IAEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditIABill: function () {
        var item = this.iaBillGrid.getSelectionModel().getSelection();

        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要编辑的采购单");
            return;
        }
        var pwBill = item[0];
        //判断采购订单的状态，看是否可以编辑
        if(pwBill.get("billStatus") == "已入库"){
            ERP.MsgBox.showInfo("采购单已经入库，无法编辑");
            return;
        }
        var form = Ext.create("ERP.Purchase.IAEditForm", {
            parentForm: this,
            entity: pwBill
        });
        form.show();
    },
    onDeleteIABill: function () {
        var me = this;
        var item = me.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的验收单");
            return;
        }
        
        var pwBill = item[0];
        var store = me.iaBillGrid.getStore();
        var index = store.findExact("id", pwBill.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        var info = "请确认是否废弃验收单: <span style='color:red'>" + pwBill.get("ref") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvAcceptance/deleteIABill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成废弃操作", function () {
                                me.refreshIABillGrid(preIndex);
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
    onIABillGridSelect: function () {
        this.refreshIABillDetailGrid();
    },
    refreshIABillDetailGrid: function (id) {
        var me = this;
        me.iaBillDetailGrid.setTitle("验收单明细");
        var grid = me.iaBillGrid;
        var item = me.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var pwBill = item[0];

        var grid = me.iaBillDetailGrid;
        grid.setTitle("单号: " + pwBill.get("ref") + " 供应商: " + pwBill.get("supplierName") + " 入库仓库: " + pwBill.get("warehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/InvAcceptance/iaBillDetailList",
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
        var item = this.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的验收单");
            return;
        }
        var pwBill = item[0];

        var detailCount = this.iaBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前验收单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的验收单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvAcceptance/commitIABill",
                timeout:300000,
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshIABillGrid();
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
    onQueren: function () {
        var item = this.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要确认的验收单");
            return;
        }
        var pwBill = item[0];

        var info = "请确认是否确认单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的验收单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在确认中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvAcceptance/querenIABill",
                timeout:300000,
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成确认操作", function () {
                                me.refreshIABillGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                            ERP.MsgBox.showInfo("成功完成确认操作", function () {
                                me.refreshIABillGrid();
                            });
                    }
                }
            });
        });
    },
    onShoukuan: function () {
        var item = this.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要付款的验收单");
            return;
        }
        var pwBill = item[0];

        var info = "请确认是否付款单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的验收单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在付款中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvAcceptance/shoukuanIABill",
                timeout:300000,
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();
                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成付款操作", function () {
                                me.refreshIABillGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                            ERP.MsgBox.showInfo("成功完成付款操作", function () {
                                me.refreshIABillGrid();
                            });
                    }
                }
            });
        });
    },
    
    gotoIABillGridRecord: function (id) {
        var me = this;
        var grid = me.iaBillGrid;
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
    onExport:function(){
        var me = this;
        var result = {
                
            };
            
            if(Ext.getCmp("editBeginDate").getValue()){
                var begindate = Ext.Date.format(Ext.getCmp("editBeginDate").getValue(), 'Y-m-d');
                result.begindate = begindate;
            }
            if(Ext.getCmp("editEndDate").getValue()){
                var enddate = Ext.Date.format(Ext.getCmp("editEndDate").getValue(), 'Y-m-d');
                result.enddate = enddate;
            }
            if(Ext.getCmp("editSearchSupplier").getValue()){
                var supplier = Ext.getCmp("editSearchSupplier").getValue();
                result.supplier = supplier;
            }
            if(Ext.getCmp("editSearchGoodsCode").getValue()){
                var goods_code = Ext.getCmp("editSearchGoodsCode").getValue();
                result.goods_code = goods_code;
            }
            if(Ext.getCmp("auto")){
                var auto = Ext.getCmp("auto").getValue();
                result.auto = auto;
            }
        var params = result;
        var send_parm = "";
        for(i in params){
            send_parm+="&"+i+"="+params[i];
        }
        url = ERP.Const.BASE_URL + "Home/InvAcceptance/iabillList?act=export" + send_parm + "&start=0&limit=10000";
        window.open(url);
    },
    onExportDetail:function(){
        var me = this;
        me.iaBillDetailGrid.setTitle("验收单明细");
        var grid = me.iaBillGrid;
        var item = me.iaBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var pwBill = item[0];
        var pwBillId = pwBill.get("id");

        url = ERP.Const.BASE_URL + "Home/InvAcceptance/iaBillDetailList?act=export&pwBillId="+pwBillId;
        window.open(url);
    }
});