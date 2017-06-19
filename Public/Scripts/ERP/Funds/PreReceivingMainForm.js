// 预收款管理 - 主界面
Ext.define("ERP.Funds.PreReceivingMainForm", {
	extend : "Ext.panel.Panel",

	border : 0,
	layout : "border",

	initComponent : function() {
		var me = this;
		
		var modelName = "PSICustomerCategroy";
		Ext.define(modelName, {
			extend : "Ext.data.Model",
			fields : [ "id", "name" ]
		});

		Ext.apply(me, {
			tbar : [ 
			{
				text: "收取预收款",
				iconCls: "ERP-button-add",
				handler: me.onReceivingMoney,
				scope: me
			}, "-", {
				text: "退还预收款",
				iconCls: "ERP-button-delete",
				handler: me.onReturnMoney,
				scope: me
			}, "-",
			{
				xtype : "displayfield",
				value : "客户分类"
			}, {
				xtype : "combobox",
				id : "comboCategory",
				queryMode : "local",
				editable : false,
				valueField : "id",
				displayField : "name",
				store : Ext.create("Ext.data.Store", {
					model : modelName,
					autoLoad : false,
					data : []
				})
			}, {
				text : "查询",
				iconCls : "ERP-button-refresh",
				handler : me.onQuery,
				scope : me
			}, "-", {
				text : "关闭",
				iconCls : "ERP-button-exit",
				handler : function() {
					location.replace(ERP.Const.BASE_URL);
				}
			} ],
			layout : "border",
			border : 0,
			items : [ {
				region : "center",
				layout : "fit",
				border : 0,
				items : [ me.getMainGrid() ]
			}, {
				region : "south",
				layout : "fit",
				border : 0,
				split : true,
				height : "50%",
				items : [ me.getDetailGrid() ]
			} ]
		});

		me.callParent(arguments);
		
		me.queryCustomerCategory();
	},

	getMainGrid : function() {
		var me = this;
		if (me.__mainGrid) {
			return me.__mainGrid;
		}

		var modelName = "PSIPreReceiving";
		Ext.define(modelName, {
			extend : "Ext.data.Model",
			fields : [ "id", "customerId", "code", "name", "inMoney", "outMoney",
					"balanceMoney" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : modelName,
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/prereceivingList",
				reader : {
					root : 'dataList',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
			Ext.apply(store.proxy.extraParams, {
				categoryId : Ext.getCmp("comboCategory").getValue()
			});
		});

		me.__mainGrid = Ext.create("Ext.grid.Panel", {
			viewConfig: {
                enableTextSelection: true
            },
            border: 0,
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "客户编码",
				dataIndex : "code",
				menuDisabled : true,
				sortable : false,
				width: 120
			}, {
				header : "客户名称",
				dataIndex : "name",
				menuDisabled : true,
				sortable : false,
				width: 300
			}, {
				header : "收",
				dataIndex : "inMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			}, {
				header : "支",
				dataIndex : "outMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			}, {
				header : "预付款余额",
				dataIndex : "balanceMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			} ],
			store : store,
			listeners : {
				select : {
					fn : me.onMainGridSelect,
					scope : me
				}
			}
		});

		return me.__mainGrid;
	},

	getDetailParam : function() {
		var item = this.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return null;
		}

		var rv = item[0];
		return rv.get("customerId");
	},

	onMainGridSelect : function() {
		this.getDetailGrid().getStore().loadPage(1);
	},

	getDetailGrid : function() {
		var me = this;
		if (me.__detailGrid) {
			return me.__detailGrid;
		}

		var modelName = "PSIPreReceivingDetail";
		Ext.define(modelName, {
			extend : "Ext.data.Model",
			fields : [ "id", "inMoney", "outMoney", "balanceMoney", "refType",
					"refNumber", "bizDT", "dateCreated", "bizUserName", "inputUserName" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : modelName,
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/prereceivingDetailList",
				reader : {
					root : 'dataList',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
			Ext.apply(store.proxy.extraParams, {
				customerId : me.getDetailParam()
			});
		});

		me.__detailGrid = Ext.create("Ext.grid.Panel", {
			viewConfig: {
                enableTextSelection: true
            },
			title : "预收款明细",
			border: 0,
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "业务类型",
				dataIndex : "refType",
				menuDisabled : true,
				sortable : false,
				width: 120
			}, {
				header : "单号",
				dataIndex : "refNumber",
				menuDisabled : true,
				sortable : false,
				width : 120,
				renderer: function(value, md, record) {
					return "<a href='" + ERP.Const.BASE_URL + "Home/Bill/viewIndex?fid=2025&refType=" 
						+ encodeURIComponent(record.get("refType")) 
						+ "&ref=" + encodeURIComponent(record.get("refNumber")) + "' target='_blank'>" + value + "</a>";
				}
			}, {
				header : "业务日期",
				dataIndex : "bizDT",
				menuDisabled : true,
				sortable : false
			}, {
				header : "收",
				dataIndex : "inMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			}, {
				header : "支",
				dataIndex : "outMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			}, {
				header : "预收款余额",
				dataIndex : "balanceMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn",
				width: 160
			},{
				header : "创建时间",
				dataIndex : "dateCreated",
				menuDisabled : true,
				sortable : false,
				width: 140
			},{
				header : "业务员",
				dataIndex : "bizUserName",
				menuDisabled : true,
				sortable : false,
				width: 120
			},{
				header : "制单人",
				dataIndex : "inputUserName",
				menuDisabled : true,
				sortable : false,
				width: 120
			} ],
			store : store
		});

		return me.__detailGrid;
	},

	onQuery : function() {
		var me = this;
		
		me.getMainGrid().getStore().removeAll();
		me.getDetailGrid().getStore().removeAll();

		me.getMainGrid().getStore().loadPage(1);
    },
    
    queryCustomerCategory: function() {
    	var combo = Ext.getCmp("comboCategory");
        var el = Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Customer/categoryList",
            method: "POST",
            callback: function (options, success, response) {
                var store = combo.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (store.getCount() > 0) {
                    	combo.setValue(store.getAt(0).get("id"));
                    }
                }

                el.unmask();
            }
        });
    },
    
    onReceivingMoney: function() {
    	var form = Ext.create("ERP.Funds.AddPreReceivingForm", {
    		parentForm: this
    	});
    	form.show();
    },
    
    onReturnMoney: function() {
    	var form = Ext.create("ERP.Funds.ReturnPreReceivingForm", {
    		parentForm: this
    	});
    	form.show();
    }
});