// 销售出库 - 主界面
Ext.define("ERP.Sale.WSMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPWSBill", {
            extend: "Ext.data.Model",
            fields: ["id","payStatusStr", "order_sn", "ref", "bizDate", "deliveryTime", "customerName",'bill_status',
                "consignee", "tel", "address","billStatus", "billStatusStr", "amount", "shipping_fee", "realamount", "discount","sitename","box", "remark","type"]
        });
        var storeWSBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPWSBill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Sale/wsbillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storeWSBill.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoWSBillGridRecord(me.__lastId);
            }
        });
        storeWSBill.on("beforeload", function () {
            storeWSBill.proxy.extraParams = me.getQueryParam();
        });
        var tbarEx = Ext.create('Ext.toolbar.Toolbar',{
            id : "tbarEx",
            items:[
                {
                    id: "search_add_time_start",
                    xtype: "datefield",
                    format: "Y-m-d H",
                    labelAlign: "left",
                    labelSeparator: "",
                    width : 180,
                    value:'',
                    labelWidth : 50,
                    fieldLabel: "下单时间",
                },

                {
                    id: "search_add_time_end",  
                    xtype: "datefield",
                    format: "Y-m-d H",
                    labelAlign: "right",
                    labelSeparator: "",
                    labelWidth : 8,
                    width :200,
                    fieldLabel: "到",
                    value:''
                },  
                {
                    xtype : "combo",
                    id : "search_bill_status",  
                    queryMode : "local",
                    editable : false,   
                    valueField : "id",
                    fieldLabel:'订单状态', 
                    labelWidth : 60,
                    labelAlign : "right",
                    width:120,
                    store : Ext.create("Ext.data.ArrayStore", {
                        fields : [ "id", "text" ],
                        data : [ [ "-2", "所有" ], [ "-1", "待提交" ], [ "0", "待拣货" ],[ "1", "拣货中" ],[ "2", "已拣货" ],[ "3", "已出库" ],[ "4", "已到站" ],[ "5", "已取货" ],[ "6", "退货" ] ]
                    }),
                    value: '-2'
                },
                {
                    id: "search_mall_order_ref",
                    labelWidth : 50,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:180,
                    fieldLabel : "电商单号",
                    margin: "5, 0, 0, 0",
                    xtype: "textfield",
                },
                {
                    id: "search_customer",
                    labelWidth : 50,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:180,
                    fieldLabel : "客户",
                    margin: "5, 0, 0, 0",
                    xtype: "textfield",
                },
            ]
        });
        me.tbarEx = tbarEx;
        var tbar = Ext.create('Ext.toolbar.Toolbar',{
            items:[
            {
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storeWSBill,
                    displayInfo : true,
                    displayMsg: '第 {0} 条到 {1} 条，共{2}条',
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
                    value: "条记录",
                },"-",
                {
                    xtype: "displayfield",
                    value: "日期:",
                },
                {id: "start_time",xtype: "datefield",format: "Y-m-d",labelAlign: "left",labelSeparator: "",width : 100,value:(new Date())},

                {id: "end_time",  xtype: "datefield",format: "Y-m-d",labelAlign: "right",labelSeparator: "",labelWidth : 8,width :120,fieldLabel: "到",value:(new Date())},  
                /*
                {xtype : "combo",id : "delivery_time",width:60, queryMode : "local",editable : false,   valueField : "id",
                store : Ext.create("Ext.data.ArrayStore", {
                    fields : [ "id", "text" ],
                    data : [ [ "0", "全部" ], [ "1", "上午" ], [ "2", "下午" ] ]
                }),
                value: '0'
                },
                */
                {
                    id: "mobile",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "手机",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "username",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "姓名",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    xtype : "combo",
                    id : "orderType",  
                    queryMode : "local",
                    editable : false,   
                    valueField : "id",
                    fieldLabel:'订单类型', 
                    labelWidth : 60,
                    labelAlign : "right",
                    width:140,
                    store : Ext.create("Ext.data.ArrayStore", {
                        fields : [ "id", "text" ],
                        data : [ [ "0", "全部" ], [ "1", "电商订单" ], [ "10", "批发订单" ] ]
                    }),
                    value: '0'
                },   
                {
                    text : "查询",
                    iconCls : "ERP-button-refresh",
                    handler : me.refreshSaleBillGrid,
                    scope : me
                },               
                {
                    text : "导出",
                    iconCls : "ERP-button-exit",
                    handler : me.exportdata,
                    scope : me
                }
            ]
        });

        var gridWSBill = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [{
                    header: "状态",
                    dataIndex: "billStatusStr",
                    menuDisabled: true,
                    sortable: false,
                    width: 45,
                    renderer: function (value) {
                        return value;
                    }
                }, 
                {
                    header: "电商单号",
                    dataIndex: "order_sn",
                    width: 120,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "单号",
                    dataIndex: "ref",
                    width: 120,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "下单时间",
                    dataIndex: "bizDate",
                    width: 150,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "送货日期",
                    dataIndex: "deliveryTime",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "客户",
                    dataIndex: "customerName",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "收货人",
                    dataIndex: "consignee",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "电话",
                    dataIndex: "tel",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "收货地址",
                    dataIndex: "address",
                    width: 200,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "销售金额",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                },{
                    header: "运费",
                    dataIndex: "shipping_fee",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                },{
                    header: "实付金额",
                    dataIndex: "realamount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                },
                {
                    header: "柜信息",
                    dataIndex: "box",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "折扣",
                    dataIndex: "discount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                },{
                    header: "备注",
                    dataIndex: "remark",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 100
                }, 
                /*{
                    header: "送货时间",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }*/],
            listeners: {
                select: {
                    fn: me.onWSBillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    //fn: me.onEditWSBill,
                    //scope: me
                }
            },
            store: storeWSBill,
            //tbar: tbar
            dockedItems: [tbar, tbarEx],
        });

        Ext.define("ERPWSBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName",
                "goodsCount", "goodsMoney", "goodsPrice","applyCount","applyNum","applyPrice"]
        });
        var storeWSBillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPWSBillDetail",
            data: []
        });

        var gridWSBillDetail = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            title: "销售出库单明细",
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
                    header: "数量",
                    dataIndex: "goodsCount",
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
                    header: "单价",
                    dataIndex: "goodsPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                }, {
                    header: "销售金额",
                    dataIndex: "goodsMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                },
                {
                    header: "实际数量",
                    dataIndex: "applyCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                },
                {
                    header: "实际重量",
                    dataIndex: "applyNum",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                },
                {
                    header: "实际金额",
                    dataIndex: "applyPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 150
                }

                ],
            store: storeWSBillDetail
        });

        Ext.apply(me, {
            tbar: [
                
                {
                    text: "新建销售出库单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddWSBill
                }, 
                "-", {
                    text: "编辑销售出库单",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onEditWSBill
                },
                 "-", {
                    text: "删除手动销售出库单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteWSBill
                },"-",{
                    text: "撤销未出库的电商订单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteMallWSBill
                },"-", {
                    text: "手动出库",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onCommit
                },"-", {
                    text: "打印单据",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onPrint
                }
                ],
            items: [{
                    region: "north",
                    height: "70%",
                    split: true,
                    layout: "fit",
                    border: 0,
                    items: [gridWSBill]
                }, {
                    region: "center",
                    layout: "fit",
                    border: 0,
                    items: [gridWSBillDetail]
                }]
        });

        me.wsBillGrid = gridWSBill;
        me.wsBillDetailGrid = gridWSBillDetail;

        me.callParent(arguments);

        me.refreshWSBillGrid();
    },
    refreshWSBillGrid: function (id) {
        var gridDetail = this.wsBillDetailGrid;
        gridDetail.setTitle("销售出库单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    exportdata: function (id) {
        var search_add_time_start = '';
        var search_add_time_end = '';
        if(Ext.getCmp("search_add_time_start").getValue()) {
            search_add_time_start = Ext.getCmp("search_add_time_start").getValue()
        }
        if(Ext.getCmp("search_add_time_end").getValue()) {
            search_add_time_end = Ext.getCmp("search_add_time_end").getValue()
        }
        var ext_data = "&search_add_time_start="+search_add_time_start+ "&search_add_time_end="+search_add_time_end+
                        "&search_mall_order_ref="+Ext.getCmp("search_mall_order_ref").getValue()+"&search_customer="+Ext.getCmp("search_customer").getValue()+"&search_bill_status="+Ext.getCmp("search_bill_status").getValue();
			send_parm = '&startdate=' + Ext.Date.format(Ext.getCmp("start_time").getValue(), 'Y-m-d') + '&enddate=' + Ext.Date.format(Ext.getCmp("end_time").getValue(), 'Y-m-d')
				+'&ordertype=' + Ext.getCmp("orderType").getValue() + '&mobile=' + Ext.getCmp("mobile").getValue() + '&username=' + Ext.getCmp("username").getValue()
				+ '&page=1&start=0&limit=2000';
      //console.warn(url);
      url = ERP.Const.BASE_URL + "Home/Sale/wsbillList?act=export" + ext_data+send_parm;
      console.warn(url);
			window.open(url);
    },
    onAddWSBill: function () {
        var form = Ext.create("ERP.Sale.WSEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditWSBill: function () {
        var item = this.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的销售出库单");
            return;
        }
        var wsBill = item[0];
        var type = wsBill.get("type");
        if(type != 10){
            ERP.MsgBox.showInfo("该订单无法编辑");
            return;
        }
        var form = Ext.create("ERP.Sale.WSEditForm", {
            parentForm: this,
            entity: wsBill
        });
        form.show();
    },
    onDeleteWSBill: function () {
        var item = this.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的销售出库单");
            return;
        }
        var wsBill = item[0];

        var info = "请确认是否删除销售出库单: <span style='color:red'>" + wsBill.get("ref")
                + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/deleteWSBill",
                method: "POST",
                params: {
                    id: wsBill.get("id")
                },
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                me.refreshWSBillGrid();
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


    onDeleteMallWSBill: function () {
        var item = this.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的销售出库单");
            return;
        }
        var wsBill = item[0];

        var info = "请确认是否撤销销售出库单: <span style='color:red'>" + wsBill.get("ref")
                + "</span>对应的电商订单";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/deleteMallWSBill",
                method: "POST",
                params: {
                    id: wsBill.get("id")
                },
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成撤销操作", function () {
                                me.refreshWSBillGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            //window.location.reload();
                        });
                    }
                }

            });
        });
    },

    onWSBillGridSelect: function () {
        this.refreshWSBillDetailGrid();
    },
    refreshWSBillDetailGrid: function (id) {
        var me = this;
        me.wsBillDetailGrid.setTitle("销售出库单明细");
        var grid = me.wsBillGrid;
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.wsBillDetailGrid;
        grid.setTitle("单号: " + bill.get("ref") + " 客户: "
                + bill.get("customerName") );
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/wsBillDetailList",
            params: {
                billId: bill.get("ref")
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
    refreshWSBillInfo: function () {
        var me = this;
        var item = me.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/refreshWSBillInfo",
            method: "POST",
            params: {
                id: bill.get("id")
            },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    bill.set("amount", data.amount);
                    me.wsBillGrid.getStore().commitChanges();
                }
            }
        });
    },
    onCommit: function () {
        var me = this;
        var item = me.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的销售出库单");
            return;
        }
        var bill = item[0];

        var detailCount = this.wsBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前销售出库单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的销售出库单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/commitWSBill",
                method: "POST",
                params: {id: bill.get("ref")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshWSBillGrid(data.id);
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
    onPrint: function () {
        var me = this;
        var item = me.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要打印的销售出库单");
            return;
        }
        var bill = item[0];
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/wsBillDetailListPrint",
            params: {
                billId: bill.get("ref")
            },
            method: "POST",
            callback: function (options, success, response) {

                if (success) {
                    CreateReport("Report");
                        //读取报表模版
                        Report.LoadFromURL("/erp/baobiao/grf/6i.aca");
                        //加载报表数据
                        Report.LoadDataFromXML(Ext.JSON.decode(response.responseText));
                        //打印预览
                        Report.PrintPreview(true);
//                        Report.Start();
                }

            }
        });
    },
    
    gotoWSBillGridRecord: function(id) {
        var me = this;
        var grid = me.wsBillGrid;
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
    refreshSaleBillGrid:function(){
        Ext.getCmp("pagingToobar").doRefresh();
    },
    getQueryParam: function() {
        var me = this;
        var result = {
            startdate: Ext.getCmp("start_time").getValue(),
            enddate: Ext.getCmp("end_time").getValue(),
            //delivery_time: Ext.getCmp("delivery_time").getValue(),
            //sell_type: Ext.getCmp("sell_type").getValue(),
            //order_sn: Ext.getCmp("order_sn").getValue(),
            //goods_code: Ext.getCmp("goods_code").getValue(),
            //only_code: Ext.getCmp("only_code").getValue(),
            //goods_bar: Ext.getCmp("goods_bar").getValue(),
            //supplier: Ext.getCmp("supplier_name").getValue(),
            //site: Ext.getCmp("site_name").getValue(),
            //limit:Ext.getCmp("comboCountPerPage").getValue(),
            mobile:Ext.getCmp("mobile").getValue(),
            username:Ext.getCmp("username").getValue(),
            ordertype:Ext.getCmp("orderType").getValue(),
            search_add_time_start:Ext.getCmp("search_add_time_start").getValue(),
            search_add_time_end:Ext.getCmp("search_add_time_end").getValue(),
            search_mall_order_ref : Ext.getCmp("search_mall_order_ref").getValue(),
            search_bill_status : Ext.getCmp("search_bill_status").getValue(),
            search_customer:Ext.getCmp("search_customer").getValue(),
        };
        return result;
    }, 
});