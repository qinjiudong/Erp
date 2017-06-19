// 安全库存调拨明细表
Ext.define("PSI.Report.TransferInventoryForm", {
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
                        id: "editOutShop",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "出库店铺"
                    },
                    {
                        id: "editInShop",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "入库店铺"
                    },
                    {
                        id: "editGoodsCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "商品条码"
                    },
            {
            	text: "查询",
            	iconCls: "ERP-button-refresh",
            	handler: me.onQuery,
            	scope: me
            	}, "-",
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
    	
    	var modelName = "PSIReportTransferInventory";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["goodsBarCode", "begin_balance_count", "begin_balance_money",
                "end_balance_count", "goodsCode", "goodsName", "goodsSpec", "unitName", "end_balance_money"]
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
                url: ERP.Const.BASE_URL + "Home/Report/transferInventoryQueryData",
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
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false, width: 200},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 160},
                {header: "计量单位", dataIndex: "unitName", menuDisabled: true, sortable: false},
                {header: "出库店铺", dataIndex: "outShop", menuDisabled: true, sortable: false},
                {header: "入库店铺", dataIndex: "inShop", menuDisabled: true, sortable: false, width: 160},
                {header: "调拨数量", dataIndex: "number", menuDisabled: true, sortable: false},
                {header: "调拨日期", dataIndex: "date", menuDisabled: true, sortable: false}
                
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
        var out_shop = Ext.getCmp("editOutShop").getValue();
        if(out_shop){
            result.out_shop = out_shop;
        }
        var in_shop = Ext.getCmp("editInShop").getValue();
        if(in_shop){
            result.out_shop = in_shop;
        }
        
        return result;
    },
    
    refreshMainGrid: function (id) {
        Ext.getCmp("pagingToobar").doRefresh();
    }
});