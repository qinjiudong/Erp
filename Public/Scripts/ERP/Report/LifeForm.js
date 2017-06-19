// 应付账款账龄分析表
Ext.define("PSI.Report.LifeForm", {
    extend: "Ext.panel.Panel",
    
    border: 0,
    
    layout: "border",

    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            tbar: [{
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
            },
            /*

            {
            	region: "south", layout: "fit", border: 0,
            	height: 90,
            	items: [me.getSummaryGrid()]
            }
			*/
            ]
        });

        me.callParent(arguments);
    },
    
    getMainGrid: function() {
    	var me = this;
    	if (me.__mainGrid) {
    		return me.__mainGrid;
    	}
    	
    	var modelName = "lifeModel";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["goodsCode", "goodsName", "goodsBar", "goodsUnit", "goodsSpec", 
                     "goodsLife","goodsLastSaleTime","balanceCount", "status_str"]
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
                url: ERP.Const.BASE_URL + "Home/Report/lifeDataQuery",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
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
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false},
                {header: "商品条码", dataIndex: "goodsBar", menuDisabled: true, sortable: false, width: 100},
                {header: "商品规格", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 100},
                {header: "商品单位", dataIndex: "goodsUnit", menuDisabled: true, sortable: false, width: 100},
                {header: "商品保质期", dataIndex: "goodsLife", menuDisabled: true, sortable: false, width: 100},
                {header: "上次销售时间", dataIndex: "goodsLastSaleTime", menuDisabled: true, sortable: false, width: 100},
                {header: "库存", dataIndex: "balanceCount", menuDisabled: true, sortable: false, width: 100},
                {header: "上架", dataIndex: "status_str", menuDisabled: true, sortable: false, width: 50},
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
    	
    	var modelName = "PSIReceivablesSummary";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["balanceMoney", "money30", "money30to60", "money60to90", "money90"]
        });

        me.__summaryGrid = Ext.create("Ext.grid.Panel", {
        	title: "应付账款汇总",
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            border: 0,
            columns: [
                {header: "当期余额", dataIndex: "balanceMoney", width: 120, menuDisabled: true, 
                	sortable: false, align: "right", xtype: "numbercolumn"},
                {header: "账龄30天内", dataIndex: "money30", width: 120, menuDisabled: true, 
                		sortable: false, align: "right", xtype: "numbercolumn"},
                {header: "账龄30-60天", dataIndex: "money30to60", menuDisabled: true, 
                			sortable: false, align: "right", xtype: "numbercolumn"},
                {header: "账龄60-90天", dataIndex: "money60to90", menuDisabled: true, 
                				sortable: false, align: "right", xtype: "numbercolumn"},
                {header: "账龄大于90天", dataIndex: "money90", menuDisabled: true, 
                					sortable: false, align: "right", xtype: "numbercolumn"}
            ],
            store: Ext.create("Ext.data.Store", {
                model: modelName,
                autoLoad: false,
                data: []
            })
        });

        return me.__summaryGrid;
    },
    
    onQuery: function() {
    	this.refreshMainGrid();
    	//this.querySummaryData();
    },
    
    refreshMainGrid: function (id) {
        Ext.getCmp("pagingToobar").doRefresh();
    },
    
    querySummaryData: function() {
    	var me = this;
        var grid = me.getSummaryGrid();
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Report/payablesSummaryQueryData",
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
    }
});