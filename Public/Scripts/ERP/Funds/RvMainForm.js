// 应收账款 - 主界面
Ext.define("ERP.Funds.RvMainForm", {
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
					data : [ [ "customer", "客户" ], [ "supplier", "供应商" ] ]
				}),
				value : "customer",
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
				value : "名称"
			}, {
				xtype : "textfield",
				id : "editCustomer"
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
				items : [ me.getRvGrid() ]
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
					items : [ me.getRvDetailGrid() ]
				}, {
					region : "east",
					layout : "fit",
					border : 0,
					width : "40%",
					split : true,
					items : [ me.getRvRecordGrid() ]
				} ]
			} ]
		});

		me.callParent(arguments);

		me.onComboCASelect();
	},

	getRvGrid : function() {
		var me = this;
		if (me.__rvGrid) {
			return me.__rvGrid;
		}

		Ext.define("ERPRv", {
			extend : "Ext.data.Model",
			fields : [ "id", "caId", "code", "name", "rvMoney", "actMoney",
					"balanceMoney" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPRv",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/rvList",
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
				start_date:Ext.Date.format(Ext.getCmp("start_date").getValue(), 'Y-m-d 00:00:00'),
				end_date:Ext.Date.format(Ext.getCmp("end_date").getValue(), 'Y-m-d 23:59:59'),
				customer:Ext.getCmp("editCustomer").getValue()
			});
		});

		me.__rvGrid = Ext.create("Ext.grid.Panel", {
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
			}, {
				header : "应收金额",
				dataIndex : "rvMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "已收金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "未收金额",
				dataIndex : "balanceMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			} ],
			store : store,
			listeners : {
				select : {
					fn : me.onRvGridSelect,
					scope : me
				}
			}
		});

		return me.__rvGrid;
	},

	getRvParam : function() {
		var item = this.getRvGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return null;
		}

		var rv = item[0];
		return rv.get("caId");
	},

	onRvGridSelect : function() {
		this.getRvRecordGrid().getStore().removeAll();
		this.getRvRecordGrid().setTitle("收款记录");
		
		this.getRvDetailGrid().getStore().loadPage(1);
	},

	getRvDetailGrid : function() {
		var me = this;
		if (me.__rvDetailGrid) {
			return me.__rvDetailGrid;
		}

		Ext.define("ERPRvDetail", {
			extend : "Ext.data.Model",
			fields : [ "id", "rvMoney", "actMoney", "balanceMoney", "refType",
					"refNumber", "bizDT", "dateCreated" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPRvDetail",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/rvDetailList",
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
				caId : me.getRvParam(),
				start_date:Ext.Date.format(Ext.getCmp("start_date").getValue(), 'Y-m-d 00:00:00'),
				end_date:Ext.Date.format(Ext.getCmp("end_date").getValue(), 'Y-m-d 23:59:59'),
				danId : Ext.getCmp("newref").getValue()
			});
		});

		me.__rvDetailGrid = Ext.create("Ext.grid.Panel", {
			title : "业务单据",
			tbar : [
                            {
				xtype : "displayfield",
				value : "单号"
			}, {
				xtype : "textfield",
				id : "newref"
			}, 
			{
				text : "查询",
				iconCls : "ERP-button-refresh",
				handler : me.onRvGridSelect,
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
				header : "应收金额",
				dataIndex : "rvMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "已收金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "未收金额",
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
					fn : me.onRvDetailGridSelect,
					scope : me
				}
			}
		});

		return me.__rvDetailGrid;
	},

	onRvDetailGridSelect : function() {
		var grid = this.getRvRecordGrid();
		var item = this.getRvDetailGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			grid.setTitle("收款记录");
			return null;
		}

		var rvDetail = item[0];

		grid.setTitle(rvDetail.get("refType") + " - 单号: "
				+ rvDetail.get("refNumber") + " 的收款记录")
		grid.getStore().loadPage(1);
	},

	getRvRecordGrid : function() {
		var me = this;
		if (me.__rvRecordGrid) {
			return me.__rvRecordGrid;
		}

		Ext.define("ERPRvRecord", {
			extend : "Ext.data.Model",
			fields : [ "id", "actMoney", "bizDate", "bizUserName",
					"inputUserName", "dateCreated", "remark" ]
		});

		var store = Ext.create("Ext.data.Store", {
			model : "ERPRvRecord",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : ERP.Const.BASE_URL + "Home/Funds/rvRecordList",
				reader : {
					root : 'dataList',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
			var rvDetail
			var item = me.getRvDetailGrid().getSelectionModel().getSelection();
			if (item == null || item.length != 1) {
				rvDetail = null;
			} else {
				rvDetail = item[0];
			}

			Ext.apply(store.proxy.extraParams, {
				refType : rvDetail == null ? null : rvDetail.get("refType"),
				refNumber : rvDetail == null ? null : rvDetail.get("refNumber")
			});
		});

		me.__rvRecordGrid = Ext.create("Ext.grid.Panel", {
			title : "收款记录",
			tbar : [ {
				text : "录入收款记录",
				iconCls : "ERP-button-add",
				handler: me.onAddRvRecord,
				scope: me
			} ],
			bbar : [ {
				xtype : "pagingtoolbar",
				store : store
			} ],
			columnLines : true,
			columns : [ {
				header : "收款日期",
				dataIndex : "bizDate",
				menuDisabled : true,
				sortable : false,
				width: 80
			}, {
				header : "收款金额",
				dataIndex : "actMoney",
				menuDisabled : true,
				sortable : false,
				align : "right",
				xtype : "numbercolumn"
			}, {
				header : "收款人",
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

		return me.__rvRecordGrid;
	},

	onComboCASelect : function() {
		var me = this;
		me.getRvGrid().getStore().removeAll();
		me.getRvDetailGrid().getStore().removeAll();
		me.getRvRecordGrid().getStore().removeAll();

		var el = Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Funds/rvCategoryList",
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
		this.getRvGrid().getStore().loadPage(1);
	},
	
	onAddRvRecord: function() {
		var me = this;
		var item = me.getRvDetailGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("请选择要做收款记录的业务单据");
			return;
		}	
		
		var rvDetail = item[0];
		
		var form = Ext.create("ERP.Funds.RvRecordEditForm", {
			parentForm: me,
			rvDetail: rvDetail
		})
		form.show();
	},
	
    refreshRvInfo: function() {
    	var me = this;
    	var item = me.getRvGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var rv = item[0];
        
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Funds/refreshRvInfo",
            method: "POST",
            params: { id: rv.get("id") },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    rv.set("actMoney", data.actMoney);
                    rv.set("balanceMoney", data.balanceMoney)
                    me.getRvGrid().getStore().commitChanges();
                }
            }

        });
    },
    
    refreshRvDetailInfo: function() {
    	var me = this;
    	var item = me.getRvDetailGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var rvDetail = item[0];
        
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Funds/refreshRvDetailInfo",
            method: "POST",
            params: { id: rvDetail.get("id") },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    rvDetail.set("actMoney", data.actMoney);
                    rvDetail.set("balanceMoney", data.balanceMoney)
                    me.getRvDetailGrid().getStore().commitChanges();
                }
            }

        });
    }
});