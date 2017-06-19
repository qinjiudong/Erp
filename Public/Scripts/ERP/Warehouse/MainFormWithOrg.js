// 仓库需求设置组织机构的时候的主界面
Ext.define("ERP.Warehouse.MainFormWithOrg", {
	extend : "Ext.panel.Panel",
	border : 0,
	layout : "border",
	initComponent : function() {
		var me = this;

		Ext.define("ERPWarehouse", {
			extend : "Ext.data.Model",
			fields : [ "id", "code", "name", "inited" ]
		});

		var grid = Ext.create("Ext.grid.Panel", {
			border : 0,
			viewConfig : {
				enableTextSelection : true
			},
			columnLines : true,
			columns : [
					{
						header : "仓库编码",
						dataIndex : "code",
						menuDisabled : true,
						sortable : false,
						width : 60
					},
					{
						header : "仓库名称",
						dataIndex : "name",
						menuDisabled : true,
						sortable : false,
						width : 200
					},
					{
						header : "建账完毕",
						dataIndex : "inited",
						menuDisabled : true,
						sortable : false,
						width : 60,
						renderer : function(value) {
							return value == 1 ? "完毕"
									: "<span style='color:red'>未完</span>";
						}
					} ],
			store : Ext.create("Ext.data.Store", {
				model : "ERPWarehouse",
				autoLoad : false,
				data : []
			}),
			listeners : {
				itemdblclick : {
					fn : me.onEditWarehouse,
					scope : me
				},
				select : {
					fn : me.onWarehouseSelect,
					scope : me
				}
			}
		});
		me.grid = grid;

		Ext.apply(me, {
			tbar : [ {
				text : "新增仓库",
				iconCls : "ERP-button-add",
				handler : this.onAddWarehouse,
				scope : this
			}, {
				text : "编辑仓库",
				iconCls : "ERP-button-edit",
				handler : this.onEditWarehouse,
				scope : this
			}, {
				text : "删除仓库",
				iconCls : "ERP-button-delete",
				handler : this.onDeleteWarehouse,
				scope : this
			}, "-",{
				text : "从组织机构视角来查看仓库",
				iconCls: "ERP-button-view",
				handler : this.onView,
				scope : this
			}, "-", {
				text : "帮助",
				iconCls : "ERP-help",
				handler : function() {
					window.open("http://my.jyshop.net/u/134395/blog/374807");
				}
			}, "-", {
				text : "关闭",
				iconCls : "ERP-button-exit",
				handler : function() {
					location.replace(ERP.Const.BASE_URL);
				}
			} ],
			items : [ {
				region : "east",
				width : "60%",
				border : 0,
				split : true,
				layout : "border",
				items : [ {
					region : "north",
					height : 200,
					split : true,
					layout : "fit",
					items : [ me.getBillGrid() ]
				}, {
					region : "center",
					layout : "fit",
					items : [ me.getOrgGrid() ]
				} ]
			}, {
				region : "center",
				xtype : "panel",
				layout : "fit",
				border : 0,
				items : [ grid ]
			} ]
		});

		me.callParent(arguments);

		me.freshGrid();
	},
	onAddWarehouse : function() {
		var form = Ext.create("ERP.Warehouse.EditForm", {
			parentForm : this
		});

		form.show();
	},
	onEditWarehouse : function() {
		var item = this.grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("请选择要编辑的仓库");
			return;
		}

		var warehouse = item[0];

		var form = Ext.create("ERP.Warehouse.EditForm", {
			parentForm : this,
			entity : warehouse
		});

		form.show();
	},
	onDeleteWarehouse : function() {
		var item = this.grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("请选择要删除的仓库");
			return;
		}

		var me = this;
		var warehouse = item[0];
		var info = "请确认是否删除仓库 <span style='color:red'>" + warehouse.get("name")
				+ "</span> ?";

		var store = me.grid.getStore();
		var index = store.findExact("id", warehouse.get("id"));
		index--;
		var preIndex = null;
		var preWarehouse = store.getAt(index);
		if (preWarehouse) {
			preIndex = preWarehouse.get("id");
		}

		ERP.MsgBox.confirm(info, function() {
			var el = Ext.getBody();
			el.mask(ERP.Const.LOADING);
			Ext.Ajax.request({
				url : ERP.Const.BASE_URL + "Home/Warehouse/deleteWarehouse",
				params : {
					id : warehouse.get("id")
				},
				method : "POST",
				callback : function(options, success, response) {
					el.unmask();
					if (success) {
						var data = Ext.JSON.decode(response.responseText);
						if (data.success) {
							ERP.MsgBox.tip("成功完成删除操作");
							me.freshGrid(preIndex);
						} else {
							ERP.MsgBox.showInfo(data.msg);
						}
					} else {
						ERP.MsgBox.showInfo("网络错误");
					}
				}
			});
		});
	},
	freshGrid : function(id) {
		var me = this;
		var grid = this.grid;
		var el = grid.getEl() || Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Warehouse/warehouseList",
			method : "POST",
			callback : function(options, success, response) {
				var store = grid.getStore();

				store.removeAll();

				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					store.add(data);

					me.gotoGridRecord(id);
				}

				el.unmask();
			}
		});
	},
	gotoGridRecord : function(id) {
		var me = this;
		var grid = me.grid;
		var store = grid.getStore();
		if (id) {
			var r = store.findExact("id", id);
			if (r != -1) {
				grid.getSelectionModel().select(r);
			} else {
				grid.getSelectionModel().select(0);
			}
		} else {
			grid.getSelectionModel().select(0);
		}
	},
	getBillGrid : function() {
		var me = this;
		if (me.__billGrid) {
			return me.__billGrid;
		}

		var modelName = "ERPWarehouse_Bill";
		Ext.define(modelName, {
			extend : "Ext.data.Model",
			fields : [ "fid", "name" ]
		});

		me.__billGrid = Ext.create("Ext.grid.Panel", {
			title : "请选择仓库",
			border : 0,
			viewConfig : {
				enableTextSelection : true
			},
			columnLines : true,
			columns : [ Ext.create("Ext.grid.RowNumberer", {
				text : "序号",
				width : 30
			}), {
				header : "业务类型",
				dataIndex : "name",
				menuDisabled : true,
				sortable : false,
				flex : 1
			} ],
			store : Ext.create("Ext.data.Store", {
				model : modelName,
				autoLoad : false,
				data : []
			}),
			listeners : {
				select : {
					fn : me.onBillGridSelect,
					scope : me
				}
			}
		});

		return me.__billGrid;
	},

	getOrgGrid : function() {
		var me = this;
		if (me.__orgGrid) {
			return me.__orgGrid;
		}

		var modelName = "ERPWarehouse_Org";
		Ext.define(modelName, {
			extend : "Ext.data.Model",
			fields : [ "id", "orgCode", "fullName" ]
		});

		me.__orgGrid = Ext.create("Ext.grid.Panel", {
			title : "请选择业务类型",
			border : 0,
			viewConfig : {
				enableTextSelection : true
			},
			columnLines : true,
			columns : [{
				header : "编码",
				dataIndex : "orgCode",
				menuDisabled : true,
				sortable : false,
				width: 100
			}, {
				header : "组织机构",
				dataIndex : "fullName",
				menuDisabled : true,
				sortable : false,
				flex : 1
			} ],
			store : Ext.create("Ext.data.Store", {
				model : modelName,
				autoLoad : false,
				data : []
			}),
			tbar : [ {
				text : "添加组织机构",
				iconCls : "ERP-button-add",
				handler : me.onAddOrg,
				scope : me
			}, "-", {
				text : "移除组织机构",
				iconCls : "ERP-button-delete",
				handler : me.onRemoveOrg,
				scope : me
			} ]
		});

		return me.__orgGrid;
	},
	onWarehouseSelect : function() {
		var me = this;
		var grid = me.grid;
		var item = grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var warehouse = item[0];
		me.getBillGrid().setTitle("仓库[" + warehouse.get("name") + "]");
		var store = me.getBillGrid().getStore();
		store.removeAll();
		store.add({
			fid : "2001",
			name : "采购入库"
		});
		store.add({
			fid : "2007",
			name : "采购退货出库"
		});
		store.add({
			fid : "2002",
			name : "销售出库"
		});
		store.add({
			fid : "2006",
			name : "销售退货入库"
		});
		store.add({
			fid : "2009",
			name : "库间调拨"
		});
		store.add({
			fid : "2010",
			name : "库存盘点"
		});


		me.getBillGrid().getSelectionModel().select(0);
	},
	onBillGridSelect : function() {
		var me = this;
		var grid = me.grid;
		var item = grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var warehouse = item[0];
		grid = me.getBillGrid();
		itemBill = grid.getSelectionModel().getSelection();
		if (itemBill == null || itemBill.length != 1) {
			return;
		}

		var bill = itemBill[0];

		me.getOrgGrid().setTitle(
				"仓库[" + warehouse.get("name") + "] - [" + bill.get("name")
						+ "]的操作人范围");

		grid = me.getOrgGrid();
		var el = grid.getEl() || Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Warehouse/warehouseOrgList",
			params : {
				warehouseId : warehouse.get("id"),
				fid : bill.get("fid")
			},
			method : "POST",
			callback : function(options, success, response) {
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
	onAddOrg : function() {
		var me = this;
		var item = me.grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("没有选择仓库");
			return;
		}
		var warehouse = item[0];

		grid = me.getBillGrid();
		itemBill = grid.getSelectionModel().getSelection();
		if (itemBill == null || itemBill.length != 1) {
			ERP.MsgBox.showInfo("请选择业务类型");
			return;
		}
		var bill = itemBill[0];

		var form = Ext.create("ERP.Warehouse.EditOrgForm", {
			parentForm : me,
			warehouseId: warehouse.get("id"),
			fid: bill.get("fid")
		});
		form.show();
	},
	onRemoveOrg : function() {
		var me = this;
		var item = me.grid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			ERP.MsgBox.showInfo("没有选择仓库");
			return;
		}
		var warehouse = item[0];

		grid = me.getBillGrid();
		itemBill = grid.getSelectionModel().getSelection();
		if (itemBill == null || itemBill.length != 1) {
			ERP.MsgBox.showInfo("请选择业务类型");
			return;
		}
		var bill = itemBill[0];
		
		grid = me.getOrgGrid();
		itemOrg = grid.getSelectionModel().getSelection();
		if (itemOrg == null || itemOrg.length != 1) {
			ERP.MsgBox.showInfo("请选择要移除的组织机构");
			return;
		}
		var org = itemOrg[0];
		
		var info = "请确认是否移除[" + org.get("fullName") + "]?";

		ERP.MsgBox.confirm(info, function() {
			var el = Ext.getBody();
			el.mask(ERP.Const.LOADING);
			Ext.Ajax.request({
				url : ERP.Const.BASE_URL + "Home/Warehouse/deleteOrg",
				params : {
					warehouseId : warehouse.get("id"),
					fid: bill.get("fid"),
					orgId: org.get("id")
				},
				method : "POST",
				callback : function(options, success, response) {
					el.unmask();
					if (success) {
						var data = Ext.JSON.decode(response.responseText);
						if (data.success) {
							ERP.MsgBox.tip("成功完成操作");
							me.onBillGridSelect();
						} else {
							ERP.MsgBox.showInfo(data.msg);
						}
					} else {
						ERP.MsgBox.showInfo("网络错误");
					}
				}
			});
		});
	},
	onView: function() {
		var form = Ext.create("ERP.Warehouse.OrgViewForm");
		form.show();
	}
});