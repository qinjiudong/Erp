// 采购审核 - 主界面
Ext.define("ERP.Purchase.PVMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {

        var me = this;

        Ext.define("ERPPVBill", {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "supplierName", "warehouseName", "inputUserName",
                "bizUserName", "billStatus", "amount", "verifyUserName"]
        });

        var storePWBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPVBill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Purchase/pwbillList?type=verify",
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


        var gridPWBill = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                {header: "状态", dataIndex: "billStatus", menuDisabled: true, sortable: false, width: 60,
                	renderer: function (value) {
                        return value == "待入库" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                },
                {header: "入库单号", dataIndex: "ref", width: 110, menuDisabled: true, sortable: false},
                {header: "业务日期", dataIndex: "bizDate", menuDisabled: true, sortable: false},
                {header: "供应商", dataIndex: "supplierName", width: 300, menuDisabled: true, sortable: false},
                {header: "采购金额", dataIndex: "amount", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "入库仓库", dataIndex: "warehouseName", menuDisabled: true, sortable: false},
                {header: "业务员", dataIndex: "bizUserName", menuDisabled: true, sortable: false},
                {header: "录单人", dataIndex: "inputUserName", menuDisabled: true, sortable: false},
                {header: "审核人", dataIndex: "verifyUserName", menuDisabled: true, sortable: false}
            ],
            store: storePWBill,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePWBill
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
                }],
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

        Ext.define("ERPPVBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount",
                "goodsMoney", "goodsPrice", "goodsType"]
        });
        var storePVBillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPVBillDetail",
            data: []
        });

        var gridPVBillDetail = Ext.create("Ext.grid.Panel", {
            title: "采购单明细",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false, width: 120},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                {header: "采购数量", dataIndex: "goodsCount", menuDisabled: true, sortable: false, align: "right"},
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "采购单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "采购金额", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, align: "right", xtype: "numbercolumn", width: 150},
                {header: "商品种类", dataIndex: "goodsType", menuDisabled: true, sortable: false, width: 100}
            ],
            store: storePVBillDetail,
            listeners: {
                itemdblclick: {
                    fn: me.onEditPWBillDetail,
                    scope: me
                }
            }
        });
        var toolbar = [
        /*
                {
                    text: "新建采购单", iconCls: "ERP-button-add", scope: me, handler: me.onAddPWBill
                }, "-", {
                    text: "编辑采购单", iconCls: "ERP-button-edit", scope: me, handler: me.onEditPWBill
                }, "-", {
                    text: "废弃采购单", iconCls: "ERP-button-delete", scope: me, handler: me.onDeletePWBill
                }, "-",
        */
                 {
                    text: "驳回", iconCls: "ERP-button-delete", scope: me, handler: me.onReject
                }, "-", {
                    text: "通过", iconCls: "ERP-button-commit", scope: me, handler: me.onCommit
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
                    items: [gridPVBillDetail]
                }]
        });

        me.pwBillGrid = gridPWBill;
        me.pwBillDetailGrid = gridPVBillDetail;

        me.callParent(arguments);

        me.refreshPWBillGrid();

    },
    refreshPWBillGrid: function (id) {
        var gridDetail = this.pwBillDetailGrid;
        gridDetail.setTitle("采购单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        this.__lastId = id;
    },
    onAddPWBill: function () {
        var form = Ext.create("ERP.Purchase.PVEditForm", {
            parentForm: this
        });
        form.show();
    },
    onEditPWBill: function () {
        return;
        var item = this.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要编辑的采购单");
            return;
        }
        var pwBill = item[0];

        var form = Ext.create("ERP.Purchase.PVEditForm", {
            parentForm: this,
            entity: pwBill
        });
        form.show();
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

        var info = "请确认是否删除采购入库单: <span style='color:red'>" + pwBill.get("ref") + "</span>";
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
                            ERP.MsgBox.showInfo("成功完成删除操作", function () {
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
            ERP.MsgBox.showInfo("没有采购单");
            return;
        }
        var pwBill = item[0];

        var detailCount = this.pwBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前采购单没有录入商品明细，无法审核通过");
            return;
        }

        var info = "请确认是否审核通过单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的采购单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/verifyPWBill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成审核操作", function () {
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
    
    onReject:function(){
        var item = this.pwBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的采购单");
            return;
        }
        var pwBill = item[0];

        var detailCount = this.pwBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            //ERP.MsgBox.showInfo("当前采购单没有录入商品明细，无法审核");
            //return;
        }

        var info = "请确认是否驳回单号: <span style='color:red'>" + pwBill.get("ref") + "</span> 的采购单?";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/rejectPWBill",
                method: "POST",
                params: {id: pwBill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成驳回操作", function () {
                                me.refreshPWBillGrid();
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
    }
});