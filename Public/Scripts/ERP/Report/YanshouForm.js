// 进销存报表
Ext.define("PSI.Report.YanshouForm", {
    extend: "Ext.panel.Panel",
    
    border: 0,
    
    layout: "border",

    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [{
                    region: "north", height: 60,
                    border: 0,
                    layout: "fit", border: 1, title: "查询条件",
                    collapsible: true,
                	layout : {
    					type : "table",
    					columns : 6
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
                        value: (new Date()).getFullYear()
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
	                        data : [ [ "0", "商品" ], [ "1", "供应商" ],  [ "3", "供应商税率" ], ["4", "商品汇总"]]
	                    }),
	                    value: '0'
                	}, 
                    {
                        id: "editGoodsCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "商品编码",
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
                            margin: "5, 0, 0, 10",
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
            fields: ["bizDT", "goodsCode", "goodsBarCode", "goodsName", "goodsSpec", "saleCount", "unitName", "saleMoney","buytax",
            		 "supplierCode", "supplierName", "ref_number", "date", "total_in_count", "buytax","total_in_money_no_tax","total_in_money","total_tax_money","total_reject_money","total_reject_money_no_tax","total_reject_money_tax",
                	 "total_money_tax_0", "tax_0", "total_money_tax_13", "total_money_no_tax_13", "tax_13", "total_money_tax_17", "total_money_no_tax_17", "tax_17"]
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
                url: ERP.Const.BASE_URL + "Home/Reports/YanshouQueryData",
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
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false},
                {header: "供应商编码", dataIndex: "supplierCode", menuDisabled: true, sortable: false},
                {header: "供应商名称", dataIndex: "supplierName", menuDisabled: true, sortable: false},
                {header: "单据", dataIndex: "ref_number", menuDisabled: true, sortable: false},
                {header: "日期", dataIndex: "date", menuDisabled: true, sortable: false},
                {header: "数量", dataIndex: "total_in_count", menuDisabled: true, sortable: false},
                {header: "无税金额", dataIndex: "total_in_money_no_tax", menuDisabled: true, sortable: false},
                {header: "税金", dataIndex: "total_tax_money", menuDisabled: true, sortable: false},
                {header: "含税金额", dataIndex: "total_in_money", menuDisabled: true, sortable: false},
                {header: "退货金额", dataIndex: "total_reject_money", menuDisabled: true, sortable: false},
                {header: "无税退货金额", dataIndex: "total_reject_money_no_tax", menuDisabled: true, sortable: false},
                {header: "退货金额税金", dataIndex: "total_reject_money_tax", menuDisabled: true, sortable: false},
                {header: "税率", dataIndex: "buytax", menuDisabled: true, sortable: false},
                {header: "0-金额", dataIndex: "total_money_tax_0", menuDisabled: true, sortable: false},
                {header: "0-税金", dataIndex: "tax_0", menuDisabled: true, sortable: false},
                {header: "13-金额", dataIndex: "total_money_tax_13", menuDisabled: true, sortable: false},
                {header: "13-税金", dataIndex: "tax_13", menuDisabled: true, sortable: false},
                {header: "17-金额", dataIndex: "total_money_tax_17", menuDisabled: true, sortable: false},
                {header: "17-税金", dataIndex: "tax_17", menuDisabled: true, sortable: false},
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
    	//this.refreshSummaryGrid();
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
      //console.warn(url);
      url = ERP.Const.BASE_URL + "Home/Reports/YanshouQueryData?act=export" + send_parm + "&start=0&limit=1000";
      console.warn(url);
            window.open(url);
    }
});