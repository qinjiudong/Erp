// 应付账款 - 主界面
Ext.define("ERP.Funds.PayMainForm", {
	extend : "Ext.panel.Panel",

	border : 0,
	layout : "border",

	initComponent : function() {
		var me = this;

		Ext.define("ERPCACategory", {
			extend : "Ext.data.Model",
			fields : [ "id", "name" ]
		});

		Ext.apply(me, {
			tbar : [ {
				xtype : "displayfield",
				value : "往来单位："
			}, {
				xtype : "combo",
				id : "comboCA",
				queryMode : "local",
				editable : false,
				valueField : "id",
				store : Ext.create("Ext.data.ArrayStore", {
					fields : [ "id", "text" ],
					data : [ [ "supplier", "供应商" ], [ "customer", "客户" ] ]
				}),
				value : "supplier",
				listeners : {
					select : {
						fn : me.onComboCASelect,
						scope : me
					}
				}
			}, {
				xtype : "displayfield",
				value : "分类"
			}, {
				xtype : "combobox",
				id : "comboCategory",
				queryMode : "local",
				editable : false,
				valueField : "id",
				displayField : "name",
				store : Ext.create("Ext.data.Store", {
					model : "ERPCACategory",
					autoLoad : false,
					data : []
				})
			}, 

			{
				xtype : "displayfield",
				value : "手机"
			}, {
				xtype : "textfield",
				id : "tel"
			}, 

			{
				xtype : "displayfield",
				value : "单号"
			}, {
				xtype : "textfield",
				id : "ref"
			}, 
			{
				xtype : "displayfield",
				value : "日期"
			}, {
				id:"start_date",
				xtype: "datefield",
                format: "Y-m-d",
			},"-",
			{
				id:"end_date",
				xtype: "datefield",
                format: "Y-m-d",
			}, 
			{
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
				items : [ me.getPayGrid() ]
			}, {
				region : "south",
				layout : "border",
				border : 0,
				split : true,
				height : "50%",
				items : [ {
					region : "center",
					border : 0,
					layout : "fit",
					items : [ me.getPayDetailGrid() ]
				}, {
					region : "east",
					layout : "fit",
					border : 0,
					width : "40%",
					split : true,
					items : [ me.getPayRecordGrid() ]
				} ]
			} ]

		});

		me.callParent(arguments);

		me.onComboCASelect();
	},
	
	getPayGrid: function() {
		var me = this;
		if (me.__payGrid) {
			return me.__payGrid;
		}

		Ext.define("ERPPay", {
			extend : "Ext.data.Model",
			fields : [ "id", "caId", "code", "name", "mobile", "payMoney", "actMoney",
					"balanceMoney" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPPay",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/payList",
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
				caType : Ext.getCmp("comboCA").getValue(),
				categoryId : Ext.getCmp("comboCategory").getValue(),
				mobile: Ext.getCmp("tel").getValue(),
				ref:Ext.getCmp("ref").getValue(),
				start_date:Ext.Date.format(Ext.getCmp("start_date").getValue(), 'Y-m-d 00:00:00'),
				end_date:Ext.Date.format(Ext.getCmp("end_date").getValue(), 'Y-m-d 23:59:59')
			});
		});

		me.__payGrid = Ext.create("Ext.grid.Panel", {
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "编码",
				dataIndex : "code",
				menuDisabled : true,
				sortable : false
			}, {
				header : "名称",
				dataIndex : "name",
				menuDisabled : true,
				sortable : false
			}, 
			{
				header : "手机",
				dataIndex : "mobile",
				menuDisabled : true,
				sortable : false
			},{
				header : "应付金额",
				dataIndex : "payMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "已付金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "未付金额",
				dataIndex : "balanceMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			} ],
			store : store,
			listeners : {
				select : {
					fn : me.onPayGridSelect,
					scope : me
				}
			}
		});

		return me.__payGrid;

	},
	
	getPayDetailGrid: function() {
		var me = this;
		if (me.__payDetailGrid) {
			return me.__payDetailGrid;
		}

		Ext.define("ERPPayDetail", {
			extend : "Ext.data.Model",
			fields : [ "id", "payMoney", "actMoney", "balanceMoney", "refType",
					"refNumber", "bizDT", "dateCreated" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPPayDetail",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/payDetailList",
				reader : {
					root : 'dataList',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
			var item = me.getPayGrid().getSelectionModel().getSelection();
			var pay;
			if (item == null || item.length != 1) {
				pay = null;
			} else {
				pay = item[0];
			}

			Ext.apply(store.proxy.extraParams, {
				caType : Ext.getCmp("comboCA").getValue(),
				caId : pay == null ? null : pay.get("caId"),
				start_date:Ext.Date.format(Ext.getCmp("start_date").getValue(), 'Y-m-d 00:00:00'),
				end_date:Ext.Date.format(Ext.getCmp("end_date").getValue(), 'Y-m-d 23:59:59'),
                danId :  Ext.getCmp("ref").getValue()
			});
		});

		me.__payDetailGrid = Ext.create("Ext.grid.Panel", {
			title : "业务单据",
			tbar : [
//                            {
//				xtype : "displayfield",
//				value : "单号"
//			}, {
//				xtype : "textfield",
//				id : "newref"
//			}, 
			{
				text : "查询",
				iconCls : "ERP-button-refresh",
				handler : me.onPayGridSelect,
				scope : me
			} ],
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "业务类型",
				dataIndex : "refType",
				menuDisabled : true,
				sortable : false
			}, {
				header : "单号",
				dataIndex : "refNumber",
				menuDisabled : true,
				sortable : false,
				width : 120
			}, {
				header : "业务日期",
				dataIndex : "bizDT",
				menuDisabled : true,
				sortable : false
			}, {
				header : "应付金额",
				dataIndex : "payMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "已付金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "未付金额",
				dataIndex : "balanceMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			},{
				header : "创建时间",
				dataIndex : "dateCreated",
				menuDisabled : true,
				sortable : false
			} ],
			store : store,
			listeners : {
				select : {
					fn : me.onPayDetailGridSelect,
					scope : me
				}
			}
		});
		return me.__payDetailGrid;

	},
	
	getPayRecordGrid: function() {
		var me = this;
		if (me.__payRecordGrid) {
			return me.__payRecordGrid;
		}

		Ext.define("ERPPayRecord", {
			extend : "Ext.data.Model",
			fields : [ "id", "actMoney", "bizDate", "bizUserName",
					"inputUserName", "dateCreated", "remark" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPPayRecord",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/payRecordList",
				reader : {
					root : 'dataList',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
			var payDetail
			var item = me.getPayDetailGrid().getSelectionModel().getSelection();
			if (item == null || item.length != 1) {
				payDetail = null;
			} else {
				payDetail = item[0];
			}

			Ext.apply(store.proxy.extraParams, {
				refType : payDetail == null ? null : payDetail.get("refType"),
				refNumber : payDetail == null ? null : payDetail.get("refNumber")
			});
		});

		me.__payRecordGrid = Ext.create("Ext.grid.Panel", {
			title : "付款记录",
			tbar : [ {
				text : "录入付款记录",
				iconCls : "ERP-button-add",
				handler: me.onAddPayment,
				scope: me
			} ],
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "付款日期",
				dataIndex : "bizDate",
				menuDisabled : true,
				sortable : false,
				width: 80
			}, {
				header : "付款金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "付款人",
				dataIndex : "bizUserName",
				menuDisabled : true,
				sortable : false,
				width: 80
			}, {
				header : "录入时间",
				dataIndex : "dateCreated",
				menuDisabled : true,
				sortable : false,
				width: 120
			}, {
				header : "录入人",
				dataIndex : "inputUserName",
				menuDisabled : true,
				sortable : false,
				width: 80
			}, {
				header : "备注",
				dataIndex : "remark",
				menuDisabled : true,
				sortable : false,
				width : 150
			} ],
			store : store
		});

		return me.__payRecordGrid;
	},
	
	onComboCASelect : function() {
		var me = this;
		me.getPayGrid().getStore().removeAll();
		me.getPayDetailGrid().getStore().removeAll();
		me.getPayRecordGrid().getStore().removeAll();

		var el = Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Funds/payCategoryList",
			params : {
				id : Ext.getCmp("comboCA").getValue()
			},
			method : "POST",
			callback : function(options, success, response) {
				var combo = Ext.getCmp("comboCategory");
				var store = combo.getStore();

				store.removeAll();

				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					store.add(data);

					if (store.getCount() > 0) {
						combo.setValue(store.getAt(0).get("id"))
					}
				}

				el.unmask();
			}
		});
	},

	onQuery : function() {
		this.getPayGrid().getStore().loadPage(1);
	},
	
//	onPayGridSelectNew: function() {
//		this.getPayRecordGrid().getStore().removeAll();
//		this.getPayRecordGrid().setTitle("付款记录");
//		this.getPayDetailGridNew().getStore().loadPage(1);
//	},
	
	onPayGridSelect: function() {
		this.getPayRecordGrid().getStore().removeAll();
		this.getPayRecordGrid().setTitle("付款记录");
		this.getPayDetailGrid().getStore().loadPage(1);
	},
	
	onPayDetailGridSelect: function() {
		var grid = this.getPayRecordGrid();
		var item = this.getPayDetailGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			grid.setTitle("付款记录");
			return null;
		}

		var payDetail = item[0];

		grid.setTitle(payDetail.get("refType") + " - 单号: "
				+ payDetail.get("refNumber") + " 的付款记录")
		grid.getStore().loadPage(1);
	},
	
	onAddPayment: function() {
		var me = this;
		var item = me.getPayDetailGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("请选择要做付款记录的业务单据");
			return;
		}	
		
		var payDetail = item[0];
		
		var form = Ext.create("ERP.Funds.PaymentEditForm", {
			parentForm: me,
			payDetail: payDetail
		})
		form.show();
	},
	
    refreshPayInfo: function() {
    	var me = this;
    	var item = me.getPayGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var pay = item[0];
        
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Funds/refreshPayInfo",
            method: "POST",
            params: { id: pay.get("id") },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    pay.set("actMoney", data.actMoney);
                    pay.set("balanceMoney", data.balanceMoney)
                    me.getPayGrid().getStore().commitChanges();
                }
            }

        });
    },
    
    refreshPayDetailInfo: function() {
    	var me = this;
    	var item = me.getPayDetailGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var payDetail = item[0];
        
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Funds/refreshPayDetailInfo",
            method: "POST",
            params: { id: payDetail.get("id") },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    payDetail.set("actMoney", data.actMoney);
                    payDetail.set("balanceMoney", data.balanceMoney)
                    me.getPayDetailGrid().getStore().commitChanges();
                }
            }

        });
    }
});