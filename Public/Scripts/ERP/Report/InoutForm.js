// 进销存报表
Ext.define("PSI.Report.InoutForm", {
    extend: "Ext.panel.Panel",
    
    border: 0,
    
    layout: "border",

    initComponent: function () {
        var me = this;
        Ext.Ajax.timeout = 300000;
        Ext.apply(me, {
            tbar: [
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [{
                    region: "north", height: 80,
                    border: 0,
                    layout: "fit", border: 1, title: "查询条件",
                    collapsible: true,
                	layout : {
    					type : "table",
    					columns : 8
    				},
    				items: [{
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
	                        data : [ [ "0", "商品" ], [ "1", "供应商" ], ["2", "类别"],  ["3", "小类别"]]
	                    }),
	                    value: '0'
                	}, 
                    {
                        id: "editGoodsCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "商品编码"
                    },
                    {
                        id: "editSupplierCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "供应商编码"
                    },
                    {
                        id: "editCategoryCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "品类编码"
                    },
                    {
                    	xtype: "container",
                    	items: [{
                            xtype: "button",
                            text: "查询",
                            width: 100,
                            margin: "5 0 0 10",
                            iconCls: "ERP-button-refresh",
                            handler: me.onQuery,
                            scope: me
                        },{
                        	xtype: "button", 
                        	text: "重置查询条件",
                        	width: 100,
                        	margin: "5, 0, 0, 10",
                        	handler: me.onClearQuery,
                        	scope: me
                        },
                        {
                            xtype: "button",
                            text: "导出",
                            width: 100,
                            margin: "5 0 0 10",
                            iconCls: "ERP-button-refresh",
                            handler: me.onExport,
                            scope: me
                        }

                        ]
                    }
    				]
                }, {
                    region: "center", layout: "border", border: 0,
                    items: [{
                    	region: "center", layout: "fit", border: 0,
                    	items: [me.getMainGrid()]
                    },{
                    	region: "south", layout: "fit", height: 100,
                    	//items: [me.getSummaryGrid()]
                    }]
                }]
        });

        me.callParent(arguments);
    },
    
    getMainGrid: function() {
    	var me = this;
    	if (me.__mainGrid) {
    		return me.__mainGrid;
    	}
    	
    	var modelName = "PSIReportSaleMonthByGoods";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["bizDT", "goodsCode", "goodsName", "categoryName", "supplierCode", "supplierName", "goodsSpec", "saleCount", "unitName", "saleMoney","gross",
                	 "rejCount", "rejMoney", "c", "m", "profit", "rate",
                	 "total_in_count","total_in_money","total_in_money_no_tax",
                     "total_in_count_yi","total_in_money_yi","total_in_money_yi_no_tax",
                     "total_in_count_pan","total_in_money_pan","total_in_money_pan_no_tax",
                     "total_out_count_sale","total_out_money_sale","total_out_money_sale_without_tax","total_out_money_sale_cost","real_total_out_money_sale_profit","real_total_out_money_sale_profit_percent",
                	 "total_out_count_sun","total_out_money_sun","total_out_money_sun_sale","total_out_money_sun_no_tax",
                	 "total_out_count_pan","total_out_money_pan","total_out_money_span_sale","total_out_money_pan_no_tax",
                	 "begin_balance_count","begin_balance_money","begin_balance_money_no_tax",
                     "end_balance_count","end_balance_money","end_balance_money_no_tax"
                	 ]
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
                timeout:300000,
                url: ERP.Const.BASE_URL + "Home/Reports/InoutQueryData",
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
                //{header: "月份", dataIndex: "bizDT", menuDisabled: true, sortable: false, width: 80},
                {header: "编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "名称", dataIndex: "goodsName", menuDisabled: true, sortable: false},
                {header: "供应商编码", dataIndex: "supplierCode", menuDisabled: true, sortable: false},
                {header: "供应商名称", dataIndex: "supplierName", menuDisabled: true, sortable: false},
                {header: "所属分类", dataIndex: "categoryName", menuDisabled: true, sortable: false},
                //{header: "毛利", dataIndex: "gross", menuDisabled: true, sortable: false},
                {header: "期初数量", dataIndex: "begin_balance_count", menuDisabled: true, sortable: false, 
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "期初金额(税)", dataIndex: "begin_balance_money", menuDisabled: true, sortable: false, width: 60,xtype: "numbercolumn"},
                {header: "期初金额(无税)", dataIndex: "begin_balance_money_no_tax", menuDisabled: true, sortable: false, width: 60,xtype: "numbercolumn"},
                {header: "进货数量", dataIndex: "total_in_count", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                /*{header: "进货成本", dataIndex: "total_in_money", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0"},*/
                {header: "进货金额", dataIndex: "total_in_money", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "进货金额(无税)", dataIndex: "total_in_money_no_tax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "升溢数量", dataIndex: "total_in_count_yi", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "升溢金额", dataIndex: "total_in_money_yi", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "升溢金额(无税)", dataIndex: "total_in_money_yi_no_tax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "盘升数量", dataIndex: "total_in_count_pan", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "盘升金额", dataIndex: "total_in_money_pan", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "盘升金额(无税)", dataIndex: "total_in_money_pan_no_tax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售数量", dataIndex: "total_out_count_sale", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "销售金额", dataIndex: "total_out_money_sale", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "销售金额(不含税)", dataIndex: "total_out_money_sale_without_tax", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "销售成本", dataIndex: "total_out_money_sale_cost", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "实际成本", dataIndex: "real_total_out_money_sale_profit", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "实际毛利率", dataIndex: "real_total_out_money_sale_profit_percent", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "耗损数量", dataIndex: "total_out_count_sun", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "耗损金额", dataIndex: "total_out_money_sun", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "耗损金额(无税)", dataIndex: "total_out_money_sun_no_tax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "盘耗数量", dataIndex: "total_out_count_pan", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "盘耗金额", dataIndex: "total_out_money_pan", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "盘耗金额(无税)", dataIndex: "total_out_money_pan_no_tax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "期末数量", dataIndex: "end_balance_count", menuDisabled: true, sortable: false, 
                	align: "right", xtype: "numbercolumn", format: "0.00"},
                {header: "期末金额(税)", dataIndex: "end_balance_money", menuDisabled: true, sortable: false, width: 60},
                {header: "期末金额(无税)", dataIndex: "end_balance_money_no_tax", menuDisabled: true, sortable: false, width: 60},
                /*
                {header: "毛利", dataIndex: "profit", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "毛利率", dataIndex: "rate", menuDisabled: true, sortable: false, align: "right"}
                */
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
    
    getSummaryGrid: function() {
    	var me = this;
    	if (me.__summaryGrid) {
    		return me.__summaryGrid;
    	}
    	
    	var modelName = "PSIReportSaleMonthByGoodsSummary";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["bizDT", "saleMoney", "rejMoney", "m", "profit", "rate"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: []
        });

        me.__summaryGrid = Ext.create("Ext.grid.Panel", {
        	title: "销售汇总",
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                //{header: "月份", dataIndex: "bizDT", menuDisabled: true, sortable: false, width: 80},
                {header: "销售出库金额", dataIndex: "saleMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "退货入库金额", dataIndex: "rejMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "净销售金额", dataIndex: "m", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                /*
                {header: "毛利", dataIndex: "profit", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "毛利率", dataIndex: "rate", menuDisabled: true, sortable: false, align: "right"}
                */
            ],
            store: store
        });
        
        return me.__summaryGrid;
    },

    onQuery: function() {
    	this.refreshMainGrid();
    	this.refreshSummaryGrid();
    },
    
    refreshSummaryGrid: function() {
    	return false;
        var me = this;
        var grid = me.getSummaryGrid();
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Report/saleReportByGoodsSummaryQueryData",
            params: me.getQueryParam(),
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);
                }

                el.unmask();
            }
        });
    },
    
    onClearQuery: function() {
    	var me = this;
    	
    	Ext.getCmp("editBegin").setValue("");
    	Ext.getCmp("editEnd").setValue((new Date()));
    	
    	me.onQuery();
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
        var category_code = Ext.getCmp("editCategoryCode").getValue();
        if(category_code){
            result.category_code = category_code;
        }
        var huizong_type = Ext.getCmp("huizong_type").getValue();
        if(huizong_type){
        	result.huizong_type = huizong_type;
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
        url = ERP.Const.BASE_URL + "Home/Reports/InoutQueryData?act=export" + send_parm + "&start=0&limit=10000";
        window.open(url);
    }
});