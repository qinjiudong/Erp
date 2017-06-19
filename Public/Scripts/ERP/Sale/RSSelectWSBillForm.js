// 补货界面，选择需要补货的销售订单
Ext.define("ERP.Sale.RSSelectWSBillForm", {
    extend: "Ext.window.Window",

    config: {
        parentForm: null
    },
    
    initComponent: function () {
        var me = this;
        //订单状态
        var status = Ext.regModel('ERPWSBillRefund', {
            fields: ["id", "name"]
        });
        var refundStatusStore = Ext.create('Ext.data.Store', {
            model: 'ERPWSBillRefund',
            data: [{"id":"0","name":"全部"},{"id":"1","name":"退货"},{"id":"2", "name":"缺货"}]
        });
        Ext.apply(me, {title: "选择需要补货的订单",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 800,
            height: 500,
            layout: "border",
            items: [{
                    region: "center",
                    border: 0,
                    bodyPadding: 10,
                    layout: "fit",
                    items: [me.getWSBillGrid()]
                },
                {
                    region: "north",
                    border: 0,
                    layout: {
                        type: "table",
                        columns: 2
                    },
                    height: 150,
                    bodyPadding: 10,
                    items: [
                        {
                            html: "<h1>选择需要补货的订单</h1>",
                            border: 0,
                            colspan: 2
                        },
                        {
                        	id: "editWSRef",
                            xtype: "textfield",
                            labelAlign: "right",
                            labelSeparator: "",
                            fieldLabel: "销售订单单号"
                        },{
                            id: "editWSRefundStatus",
                            xtype: "combo",
                            labelAlign: "right",
                            labelSeparator: "",
                            fieldLabel: "补货类别",
                            valueField: "id",
                            displayField: "name",
                            store: refundStatusStore,
                            queryMode: "local",
                            value: "0",
                            name: "WSRefundStatus",
                        },
                        /*{
                            xtype: "jyerp_customerfield",
                            id: "editWSCustomer",
                            labelAlign: "right",
                            labelSeparator: "",
                            parentCmp: me,
                            fieldLabel: "客户"
                        },*/{
                        	xtype: "hidden",
                        	id: "editWSCustomerId"
                        },{
                        	id: "editFromDT",
                            xtype: "datefield",
                            format: "Y-m-d",
                            labelAlign: "right",
                            labelSeparator: "",
                            fieldLabel: "业务日期（起）"
                        },{
                        	id: "editToDT",
                            xtype: "datefield",
                            format: "Y-m-d",
                            labelAlign: "right",
                            labelSeparator: "",
                            fieldLabel: "业务日期（止）"
                        },/*
                        {
                            xtype: "jyerp_warehousefield",
                            id: "editWSWarehouse",
                            labelAlign: "right",
                            labelSeparator: "",
                            parentCmp: me,
                            fieldLabel: "仓库"
                        },
                        */
                        {
                            xtype: "textfield",
                            id: "editTel",
                            labelAlign: "right",
                            labelSeparator: "",
                            fieldLabel: "手机"
                        },{
                        	xtype: "hidden",
                        	id: "editWSWarehouseId"
                        },{
                        	xtype: "container",
                        	items: [{
                                xtype: "button",
                                text: "查询",
                                width: 100,
                                margin: "0 0 0 10",
                                iconCls: "ERP-button-refresh",
                                handler: me.onQuery,
                                scope: me
                            	},{
                            	xtype: "button", 
                            	text: "清空查询条件",
                            	width: 100,
                            	margin: "0, 0, 0, 10",
                            	handler: me.onClearQuery,
                            	scope: me
                            	}]
                        }
                    ]
                }],
            listeners: {
                show: {
                    fn: me.onWndShow,
                    scope: me
                }
            },
            buttons: [{
                    text: "选择",
                    iconCls: "ERP-button-ok",
                    formBind: true,
                    handler: me.onOK,
                    scope: me
                }, {
                    text: "取消", handler: function () {
                        me.close();
                    }, scope: me
                }]
        });

        me.callParent(arguments);
    },
    
    onWndShow: function () {
        var me = this;
    },
    
    onOK: function () {
        var me = this;
        
        var item = me.getWSBillGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择销售订单");
            return;
        }
        var wsBill = item[0];
        me.close();
        me.getParentForm().getWSBillInfo(wsBill.get("id"));
    },

    getWSBillGrid: function() {
        var me = this;
        
        if (me.__wsBillGrid) {
            return me.__wsBillGrid;
        }
        
        var modelName = "ERPWSBill_SRSelectForm";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "ref", "bizDate", "customerName", "warehouseName", "tel",
                "inputUserName", "bizUserName", "amount","sale_money","remark","bill_status_str","siteid","sitename"]
        });
        var storeWSBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Sale/selectWSBillList?",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storeWSBill.on("beforeload", function () {
        	storeWSBill.proxy.extraParams = me.getQueryParam();
        });


        me.__wsBillGrid = Ext.create("Ext.grid.Panel", {
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 50}),
                {
                    header: "单号",
                    dataIndex: "ref",
                    width: 110,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "业务日期",
                    dataIndex: "bizDate",
                    menuDisabled: true,
                    sortable: false,
                    width:80
                }, {
                    header: "客户",
                    dataIndex: "customerName",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "手机",
                    dataIndex: "tel",
                    width: 90,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "站点",
                    dataIndex: "sitename",
                    width: 90,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "销售金额",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                },
                {
                    header: "实际金额",
                    dataIndex: "sale_money",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }, 
                {
                    header: "状态",
                    dataIndex: "bill_status_str",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 60
                }, 
                {
                    header: "备注",
                    dataIndex: "remark",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 60
                }, 
                /*
                {
                    header: "出库仓库",
                    dataIndex: "warehouseName",
                    menuDisabled: true,
                    sortable: false
                }, 
                
                {
                    header: "业务员",
                    dataIndex: "bizUserName",
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "录单人",
                    dataIndex: "inputUserName",
                    menuDisabled: true,
                    sortable: false
                }
                */],
            listeners: {
                itemdblclick: {
                    fn: me.onOK,
                    scope: me
                }
            },
            store: storeWSBill,
            bbar: [{
                    id: "srbill_selectform_pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storeWSBill
                }, "-", {
                    xtype: "displayfield",
                    value: "每页显示"
                }, {
                    id: "srbill_selectform_comboCountPerPage",
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
                                storeWSBill.pageSize = Ext.getCmp("srbill_selectform_comboCountPerPage").getValue();
                                storeWSBill.currentPage = 1;
                                Ext.getCmp("srbill_selectform_pagingToobar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }]
        });
        
        return me.__wsBillGrid;
    },
    
    onQuery: function() {
        Ext.getCmp("srbill_selectform_pagingToobar").doRefresh();
    },
    
    // CustomerField回调此方法
    __setCustomerInfo: function (data) {
        Ext.getCmp("editWSCustomerId").setValue(data.id);
    },
    
    // WarehouseField回调此方法
    __setWarehouseInfo: function (data) {
        Ext.getCmp("editWSWarehouseId").setValue(data.id);
    },
    
    getQueryParam: function() {
    	var result = {};
    	
    	var ref = Ext.getCmp("editWSRef").getValue();
    	if (ref) {
    		result.ref = ref;
    	}
    	
    	var customerId = Ext.getCmp("editWSCustomerId").getValue();
    	if (customerId) {
    		if (Ext.getCmp("editWSCustomer").getValue()) {
    			result.customerId = customerId;	
    		}
    	}
    	/*
    	var warehouseId = Ext.getCmp("editWSWarehouseId").getValue();
    	if (warehouseId) {
    		if (Ext.getCmp("editWSWarehouse").getValue()) {
    			result.warehouseId = warehouseId;	
    		}
    	}
    	*/
    	var fromDT = Ext.getCmp("editFromDT").getValue();
    	if (fromDT) {
    		result.fromDT = Ext.Date.format(fromDT, "Y-m-d");
    	}
    	
    	var toDT = Ext.getCmp("editToDT").getValue();
    	if (toDT) {
    		result.toDT = Ext.Date.format(toDT, "Y-m-d");
    	}
    	var refundStatus = Ext.getCmp("editWSRefundStatus").getValue();
        if(refundStatus >= 0){
            result.refundStatus = refundStatus;
        }
        var mobile = Ext.getCmp("editTel").getValue();
        if(mobile){
            result.mobile = mobile;
        }
    	return result;
    },
    
    onClearQuery: function() {
    	Ext.getCmp("editWSRef").setValue(null);
    	//Ext.getCmp("editWSCustomer").setValue(null);
    	Ext.getCmp("editWSCustomerId").setValue(null);
    	Ext.getCmp("editWSWarehouse").setValue(null);
    	Ext.getCmp("editWSWarehouseId").setValue(null);
    	Ext.getCmp("editFromDT").setValue(null);
    	Ext.getCmp("editToDT").setValue(null);
    }
});