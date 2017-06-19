// 库间损溢 - 主界面
Ext.define("ERP.InvLoss.InvLossMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [{
                    text: "新建损溢单",
                    iconCls: "ERP-button-add",
                    scope: me,
                    handler: me.onAddBill
                }, "-", {
                    text: "编辑损溢单",
                    iconCls: "ERP-button-edit",
                    scope: me,
                    handler: me.onEditBill
                }, "-", {
                    text: "删除损溢单",
                    iconCls: "ERP-button-delete",
                    scope: me,
                    handler: me.onDeleteBill
                }, "-", {
                    text: "提交损溢单",
                    iconCls: "ERP-button-commit",
                    scope: me,
                    handler: me.onCommit
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
        gridDetail.setTitle("损溢单明细");
        gridDetail.getStore().removeAll();
        Ext.getCmp("pagingToobar").doRefresh();
        me.__lastId = id;
    },
    
    // 新增损溢单
    onAddBill: function () {
    	var form = Ext.create("ERP.InvLoss.ITEditForm", {
    		parentForm: this
    	});
    	
    	form.show();
    },
    
    // 编辑损溢单
    onEditBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的损溢单");
            return;
        }
        var bill = item[0];

        var form = Ext.create("ERP.InvLoss.ITEditForm", {
            parentForm: me,
            entity: bill
        });
        form.show();
    },
    
    // 删除损溢单
    onDeleteBill: function () {
    	var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的损溢单");
            return;
        }
        var bill = item[0];

        var info = "请确认是否删除损溢单: <span style='color:red'>" + bill.get("ref")
                + "</span>";
        
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvLoss/deleteITBill",
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
    
    // 提交损溢单
    onCommit: function () {
        var me = this;
        var item = me.getMainGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的损溢单");
            return;
        }
        var bill = item[0];

        var detailCount = me.getDetailGrid().getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前损溢单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的损溢单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/InvLoss/commitITBill",
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
            fields: ["id", "ref", "bizDate",  "fromWarehouseName", "toWarehouseName",
                "inputUserName", "bizUserName", "billStatus", "remark"]
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
                url: ERP.Const.BASE_URL + "Home/InvLoss/itbillList",
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
            var ref = Ext.getCmp("editSearchRef").getValue();
            //var pwbilldate = Ext.getCmp("editPWBillDate").getValue();
            var beginDate = Ext.Date.format(Ext.getCmp("editBeginDate").getValue(), "Y-m-d");
            var endDate   = Ext.Date.format(Ext.getCmp("editEndDate").getValue(), "Y-m-d");
            Ext.apply(
                params,{
                    ref:ref,
                    begindate:beginDate,
                    enddate:endDate,
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
                    	return value == "待损溢" ? "<span style='color:red'>" + value + "</span>" : value;
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
                    header: "仓库",
                    dataIndex: "fromWarehouseName",
                    menuDisabled: true,
                    sortable: false,
                    width: 150
                }, {
                    header: "录单人",
                    dataIndex: "inputUserName",
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "备注",
                    dataIndex: "remark",
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
                                store.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                store.currentPage = 1;
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
                    id: "editSearchRef",
                    labelWidth : 60,
                    labelAlign : "right",
                    labelSeparator : "",
                    fieldLabel : "单据号:",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id : "editBeginDate",
                    fieldLabel : "日期",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "ILBeginBillDate",
                    width:130
                    
                },
                {
                    id : "editEndDate",
                    fieldLabel : "-",
                    labelWidth : 5,
                    labelAlign : "right",
                    labelSeparator : "",
                    xtype : "datefield",
                    format : "Y-m-d",
                    value : '',//new Date(),
                    name : "ILEndBillDate",
                    width:105
                    
                },
                {
                    xtype: "container",
                    items: [{
                        xtype: "button",
                        text: "查询",
                        width: 50,
                        iconCls: "ERP-button-refresh",
                        margin: "5, 0, 0, 20",
                        handler: me.onQueryILbill,
                        scope: me
                    },
                    /*{
                        xtype: "button",
                        text: "清空查询条件",
                        width: 100,
                        iconCls: "ERP-button-cancel",
                        margin: "5, 0, 0, 5",
                        handler: me.onClearQuery,
                        scope: me
                    }*/]
                }
                ]
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
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount","goodsPrice","goodsMoney", 'unitNamePW']
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
            title: "损溢单明细",
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
                    header: "损溢数量",
                    dataIndex: "goodsCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right"
                }, 
                {
                    header: "损溢价格",
                    dataIndex: "goodsPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right"
                },
                {
                    header: "损溢金额",
                    dataIndex: "goodsMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right"
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
        me.getDetailGrid().setTitle("损溢单明细");
        var grid = me.getMainGrid();
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.getDetailGrid();
        grid.setTitle("单号: " + bill.get("ref")  + " 仓库: "
                + bill.get("fromWarehouseName"));
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/InvLoss/itBillDetailList",
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
    onQueryILbill: function(){
        var me = this;
        me.refreshMainGrid();
    }
});