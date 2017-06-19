// 销售月报表(按商品汇总)
Ext.define("PSI.Report.SaleMonthByGoodsForm", {
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
                    region: "north", height: 80,
                    border: 0,
                    layout: "fit", border: 1, title: "查询条件",
                    collapsible: true,
                	layout : {
    					type : "table",
    					columns : 8
    				},
    				items: [{
                    	id: "editQueryYear",
                        xtype: "numberfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "年",
                        labelWidth: 20,
                        width: 100,
                        value: (new Date()).getFullYear()
                    },{
                    	id: "editQueryMonth",
                    	xtype: "combobox",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        labelWidth: 20,
                        fieldLabel: " ",
                        store: Ext.create("Ext.data.ArrayStore", {
                            fields: ["id", "text"],
                            data: [[1, "一月"], [2, "二月"], [3, "三月"], [4, "四月"], [5, "五月"],
                                   [6, "六月"], [7, "七月"], [8, "八月"], [9, "九月"], [10, "十月"],
                                   [11, "十一月"], [12, "十二月"]]
                        }),
                        valueField: "id",
                        displayFIeld: "text",
                        queryMode : "local",
    					editable : false,
                        value: (new Date()).getMonth() + 1,
                        width: 90
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
                            data : [ [ "0", "商品" ],[ "1", "供应商" ], [ "2", "分类" ] ]
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
                    /*
                    {
                        id: "editCategoryCode",
                        xtype: "textfield",
                        margin: "5, 0, 0, 0",
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "品类编码"
                    },
                    */
                    {
                        id: "editCategoryCode",
                        //xtype: "combo",
                        xtype : "jyerp_goods_category_field",
                        parentCmp : me,
                        fieldLabel: "商品分类",
                        valueField: "code",
                        displayField: "name",
                        //store: categoryStore,
                        queryMode: "local",
                        editable: true,
                        value: '',
                        name: "category_name",
                        width:"500"
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
                        },{
                            xtype: "button", 
                            text: "导出",
                            width: 100,
                            margin: "5, 0, 0, 10",
                            handler: me.onExport,
                            scope: me
                        }]
                    }
    				]
                }, {
                    region: "center", layout: "border", border: 0,
                    items: [{
                    	region: "center", layout: "fit", border: 0,
                    	items: [me.getMainGrid()]
                    },{
                    	region: "south", layout: "fit", height: 200,
                    	items: [me.getSummaryGrid()]
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
            fields: ["bizDT", "goodsCode", "goodsName", "goodsSpec", "supplierCode", "supplierName", "saleCount", "unitName","buyMoney", "saleMoney", "saleMoneyNoTax",
                "rejCount", "rejMoney", "c", "m", "profit", "rate"]
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
                url: ERP.Const.BASE_URL + "Home/Report/saleMonthByGoodsQueryData",
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
                {header: "月份", dataIndex: "bizDT", menuDisabled: true, sortable: false, width: 80},
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true, sortable: false},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false},
                {header: "供应商编码", dataIndex: "supplierCode", menuDisabled: true, sortable: false},
                {header: "供应商名称", dataIndex: "supplierName", menuDisabled: true, sortable: false},
                {header: "销售出库数量", dataIndex: "saleCount", menuDisabled: true, sortable: false, 
                	align: "right", xtype: "numbercolumn", format: "0"},
                {header: "计量单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "进货单价", dataIndex: "buyMoney", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额", dataIndex: "saleMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(无税)", dataIndex: "saleMoneyNoTax", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                /*
                {header: "退货入库数量", dataIndex: "rejCount", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0"},
                {header: "退货入库金额", dataIndex: "rejMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "净销售数量", dataIndex: "c", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn", format: "0"},
                {header: "净销售金额", dataIndex: "m", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                */
                
                {header: "毛利", dataIndex: "profit", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "毛利率", dataIndex: "rate", menuDisabled: true, sortable: false, align: "right"}
                
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
            fields: ["bizDT", "saleMoney","name","saleMoney_0","saleMoneyNoTax_0","saleMoney_13","saleMoneyNoTax_13",
            "saleMoney_17","saleMoneyNoTax_17","discount","shipping_fee", "rejMoney", "m", "profit", "rate"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
            data: []
        });

        me.__summaryGrid = Ext.create("Ext.grid.Panel", {
        	title: "月销售汇总",
        	viewConfig: {
                enableTextSelection: true
            },
            border: 0,
            columnLines: true,
            columns: [
                {header: "月份", dataIndex: "bizDT", menuDisabled: true, sortable: false, width: 80},
                {header: "名称", dataIndex: "name", menuDisabled: true, sortable: false, width: 80},
                {header: "销售出库金额", dataIndex: "saleMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                {header: "优惠金额", dataIndex: "discount", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "运费", dataIndex: "shipping_fee", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(13)", dataIndex: "saleMoney_13", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(13无)", dataIndex: "saleMoneyNoTax_13", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(17)", dataIndex: "saleMoney_17", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(17无)", dataIndex: "saleMoneyNoTax_17", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(0)", dataIndex: "saleMoney_0", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "销售出库金额(0无)", dataIndex: "saleMoneyNoTax_0", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                /*
                {header: "退货入库金额", dataIndex: "rejMoney", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                    */
                /*
                {header: "净销售金额", dataIndex: "m", menuDisabled: true, sortable: false,
                	align: "right", xtype: "numbercolumn"},
                */
                
                {header: "毛利", dataIndex: "profit", menuDisabled: true, sortable: false,
                    align: "right", xtype: "numbercolumn"},
                {header: "毛利率", dataIndex: "rate", menuDisabled: true, sortable: false, align: "right"}
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
        var me = this;
        var grid = me.getSummaryGrid();
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Report/saleMonthByGoodsSummaryQueryData",
            params: me.getQueryParam(),
            method: "POST",
            timeout:300000,
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
    	
    	Ext.getCmp("editQueryYear").setValue((new Date()).getFullYear());
    	Ext.getCmp("editQueryMonth").setValue((new Date()).getMonth() + 1);
    	
    	me.onQuery();
    },
    
    getQueryParam: function() {
    	var me = this;
    	
    	var result = {
    	};
    	
    	var year = Ext.getCmp("editQueryYear").getValue();
    	if (year) {
    		result.year = year;
    	} else {
    		year = (new Date()).getFullYear();
    		Ext.getCmp("editQueryYear").setValue(year);
    		result.year = year;
    	}
    	
    	var month = Ext.getCmp("editQueryMonth").getValue();
    	if (month) {
    		result.month = month;
    	} else {
    		month = (new Date()).getMonth() + 1;
    		Ext.getCmp("editQueryMonth").setValue(month);
    		result.month = month;
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
    onExport: function(){
        var me = this;
        var params = me.getQueryParam();
        var send_parm = "";
        for(i in params){
            send_parm+="&"+i+"="+params[i];
        }
        url = ERP.Const.BASE_URL + "Home/Report/saleMonthByGoodsQueryData?act=export" + send_parm + "&start=0&limit=1000";
        window.open(url);
        url = ERP.Const.BASE_URL + "Home/Report/saleMonthByGoodsSummaryQueryData?act=export" + send_parm + "&start=0&limit=1000";
        window.open(url);
    },
    setParentGoodsCategory: function(data){
        Ext.getCmp("editCategoryCode").setValue(data.code);
        //Ext.getCmp("editCategoryId").setValue(data.id);
    }
});