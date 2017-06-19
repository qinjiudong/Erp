// 补货单 - 主界面
Ext.define("ERP.Sale.RSMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPWSBill", {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "customerName",'bill_status',
                "consignee", "tel", "address","billStatus", "billStatusStr", "amount"]
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
                url: ERP.Const.BASE_URL + "Home/Sale/wsbillList?type=1",
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
                    width: 60,
                    renderer: function (value) {
                        return value;
                    }
                }, {
                    header: "单号",
                    dataIndex: "ref",
                    width: 150,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "业务日期",
                    dataIndex: "bizDate",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "客户",
                    dataIndex: "customerName",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                }, {
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
                }/*, {
                    header: "执行金额",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }, {
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
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storeWSBill
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

        Ext.define("ERPWSBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName",
                "goodsCount", "goodsMoney", "goodsPrice"]
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
            title: "补货单明细",
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
                }],
            store: storeWSBillDetail
        });

        Ext.apply(me, {
            tbar: [{
                    text: "新建补货单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddWSBill
                }, 
                //"-", {
                 //   text: "编辑销售出库单",
                 //   iconCls: "ERP-button-edit",
                 //   scope: me,
                 //   handler: me.onEditWSBill
                //},
                 "-", {
                    text: "删除补货单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteWSBill
                },"-", {
                    text: "提交拣货",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: me.onToPick
                }, "-", {
                    text: "导出",
                    iconCls: "ERP-button-exit",
                    handler: function () {
                       	send_parm = '&page=1&start=0&limit=2000';
						url = ERP.Const.BASE_URL + "Home/Sale/wsbillList?type=1&act=export" + send_parm;
						window.open(url);
                    }
                }
                /*
                {
                    text: "手动出库",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onCommit
                }*/
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
        gridDetail.setTitle("补货单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    onAddWSBill: function () {
        var form = Ext.create("ERP.Sale.RSEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditWSBill: function () {
        var item = this.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的补货单");
            return;
        }
        var wsBill = item[0];

        var form = Ext.create("ERP.Sale.RSEditForm", {
            parentForm: this,
            entity: wsBill
        });
        form.show();
    },
    onDeleteWSBill: function () {
        var item = this.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的补货单");
            return;
        }
        var wsBill = item[0];

        var info = "请确认是否删除补货单: <span style='color:red'>" + wsBill.get("ref")
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
    onWSBillGridSelect: function () {
        this.refreshWSBillDetailGrid();
    },
    refreshWSBillDetailGrid: function (id) {
        var me = this;
        me.wsBillDetailGrid.setTitle("补货单明细");
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
            ERP.MsgBox.showInfo("没有选择要提交的补货单");
            return;
        }
        var bill = item[0];

        var detailCount = this.wsBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前补货单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的补货单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/api/Outlib",
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
    
    onToPick: function(){
        var me = this;
        var item = me.wsBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的补货单");
            return;
        }
        var bill = item[0];

        var detailCount = this.wsBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前补货单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的补货单到待拣区？";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/toPick",
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
    }
});