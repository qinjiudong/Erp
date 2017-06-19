// 库存超上限明细表
Ext.define("PSI.Report.InventoryUpperForm", {
    extend: "Ext.panel.Panel",
    
    border: 0,
    
    layout: "border",

    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [

            {
                        id: "editBegin",
                        xtype : "datefield",
                        format : "Y-m-d",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "开始日期",
                        labelWidth: 60,
                        width: 160,
                        value: (new Date())
                    },{
                        id: "editEnd",
                        xtype : "datefield",
                        format : "Y-m-d",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        labelWidth: 60,
                        fieldLabel: "结束日期",
                        value: (new Date()),
                        width: 160
                    },
                    /*
                    {
                        xtype : "combo",
                        id : "huizong_type",  
                        //queryMode : "local",
                        editable : false,   
                        valueField : "id",
                        fieldLabel:'汇总方式', 
                        labelWidth : 60,
                        labelAlign : "right",
                        width:150,
                        store : Ext.create("Ext.data.ArrayStore", {
                            fields : [ "id", "text" ],
                            data : [ [ "0", "商品" ], [ "1", "供应商" ], ["2", "类别"] ]
                        }),
                        value: '0'
                    }, 
                    */
                    {
                        xtype : "combo",
                        id : "editIcStatus",  
                        //queryMode : "local",
                        editable : false,   
                        valueField : "id",
                        fieldLabel:'状态', 
                        labelWidth : 60,
                        labelAlign : "right",
                        width:130,
                        store : Ext.create("Ext.data.ArrayStore", {
                            fields : [ "id", "text" ],
                            data : [ ["","全部"], [ "-1", "盘点中" ],[ "0", "审核中" ], [ "1000", "盘点结束" ] ]
                        }),
                        value: ''
                    }, 
                    {
                        id: "editIcRef",
                        xtype: "textfield",
                        margin: "0, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "单号",
                        labelWidth:50,
                        width:180
                    },
                    {
                        id: "editSupplierCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "供应商编码",
                        labelWidth:100,
                        width:180
                    },
                    {
                        id: "editGoodsCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "商品编码",
                        labelWidth:100,
                        width:180
                    },
            {
            	text: "查询",
            	iconCls: "ERP-button-refresh",
            	handler: me.onQuery,
            	scope: me
            	}, "-",
                {
                text: "导出",
                iconCls: "ERP-button-refresh",
                handler: me.onExport,
                scope: me
                },
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [{
                    	region: "center", layout: "fit", border: 0,
                    	items: [me.getMainGrid()]
            }]
        });

        me.callParent(arguments);
    },
    
    getMainGrid: function() {
    	var me = this;
    	if (me.__mainGrid) {
    		return me.__mainGrid;
    	}
    	
    	var modelName = "PSIReportInventoryUpper";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["goodsBarCode", "beforeCount", "goodsCount",
                "diffCount","ref", "goodsCode", "goodsName", "goodsSpec", "unitName", "bill_status_str", "lastPrice"]
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
                url: ERP.Const.BASE_URL + "Home/Report/inventoryUpperQueryData",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        store.on("beforeload", function () {
            store.proxy.extraParams = me.getQueryParam();
        });
        me.__mainGrid = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                {xtype: "rownumberer"},
                {header: "盘点状态", dataIndex: "bill_status_str", menuDisabled: true, sortable: false},
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false, width: 200},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 160},
                {header: "最新进价", dataIndex: "lastPrice", menuDisabled: true, sortable: false, width: 160},
                {header: "盘点前", dataIndex: "beforeCount", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "盘点后", dataIndex: "goodsCount", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "差异", dataIndex: "diffCount", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "单号", dataIndex: "ref", menuDisabled: true, sortable: false},
            ],
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
                }],
            listeners: {
            }
        });
        
        return me.__mainGrid;
    },
    
    onQuery: function() {
    	this.refreshMainGrid();
    },
    getQueryParam: function() {
        var me = this;
        
        var result = {
        };
        
        var begin = Ext.getCmp("editBegin").getValue();
        if (begin) {
            begin = Ext.Date.format(begin,"Y-m-d");
            result.begin = begin;
        } else {
            
        }
        
        var end = Ext.getCmp("editEnd").getValue();
        if (end) {
            end = Ext.Date.format(end,"Y-m-d");
            result.end = end;
        } else {
        }

        var goods_code = Ext.getCmp("editGoodsCode").getValue();
        if(goods_code){
            result.goods_code = goods_code;
        }
        var supplier_code = Ext.getCmp("editSupplierCode").getValue();
        if(supplier_code){
            result.supplier_code = supplier_code;
        }
        var bill_status = Ext.getCmp("editIcStatus").getValue();
        if(bill_status){
            result.bill_status = bill_status;
        }
        var bill_ref = Ext.getCmp("editIcRef").getValue();
        if(bill_ref){
            result.bill_ref = bill_ref;
        }
        
        return result;
    },
    refreshMainGrid: function (id) {
        Ext.getCmp("pagingToobar").doRefresh();
    },
    onExport:function(){
        var me = this;
        var params = me.getQueryParam();
        var send_parm = "";
        for(i in params){
            send_parm+="&"+i+"="+params[i];
        }
        url = ERP.Const.BASE_URL + "Home/Report/inventoryUpperQueryData?act=export" + send_parm + "&start=0&limit=1000";
        window.open(url);
    }
});