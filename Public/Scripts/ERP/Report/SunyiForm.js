// 进销存报表
Ext.define("ERP.Report.SunyiForm", {
    extend: "Ext.panel.Panel",
    
    border: 0,
    
    layout: "border",

    initComponent: function () {
        var me = this;

        //原因列表获取
        Ext.define("ReasonList", {
            extend: "Ext.data.Model",
            fields: ["id", "text", "name"]
        });
        var storeReason = Ext.create("Ext.data.Store", {
            autoLoad: true,
            model: "ReasonList",
            data: [],
            pageSize: 100,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/InvLoss/reasonList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
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
	                        data : [ [ "0", "商品" ]]
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
                            xtype : "combo",
                            id : "editRemark",  
                            //queryMode : "local",
                            editable : false,   
                            valueField : "name",
                            displayField : "name",
                            allowBlank: false,
                            fieldLabel:'损溢原因', 
                            labelWidth : 60,
                            labelAlign : "right",
                            width:150,
                            store : storeReason,
                            value: ''
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
            fields: ["bizDT", "goodsCode","reason", "goodsBarCode", "goodsName", "goods_count", "goods_money"]
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
                url: ERP.Const.BASE_URL + "Home/Reports/SunyiQueryData",
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
                {header: "时间", dataIndex: "bizDT", menuDisabled: true, sortable: false, width: 80},
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "goodsBarCode", menuDisabled: true, sortable: false},
                {header: "损溢数量", dataIndex: "goods_count", menuDisabled: true, sortable: false},
                {header: "损溢金额", dataIndex: "goods_money", menuDisabled: true, sortable: false},
                {header: "原因", dataIndex: "reason", menuDisabled: true, sortable: false},
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

        var reason = Ext.getCmp("editRemark").getValue();
        if(reason){
            result.reason = reason;
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
      url = ERP.Const.BASE_URL + "Home/Reports/SunyiQueryData?act=export" + send_parm + "&start=0&limit=1000";
      console.warn(url);
            window.open(url);
    }
});