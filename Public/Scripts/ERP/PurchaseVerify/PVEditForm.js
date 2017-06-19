// 采购入库单 - 新增或编辑界面
Ext.define("ERP.Purchase.PVEditForm", {
	extend : "Ext.window.Window",
	config : {
		parentForm : null,
		entity : null
	},
	initComponent : function() {
		var me = this;
		me.__readOnly = false;
		var entity = me.getEntity();
		this.adding = entity == null;

		Ext.apply(me, {
			title : entity == null ? "新建采购单" : "编辑采购单",
			modal : true,
			onEsc : Ext.emptyFn,
			maximized: true,
			width : 1000,
			height : 600,
			layout : "border",
			defaultFocus : "editSupplier",
			tbar:["-",{
                text: "保存",
                id: "buttonSave",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me
			},  "-", {
				text : "取消",
				id: "buttonCancel",
				iconCls: "ERP-button-cancel",
				handler : function() {
					if (me.__readonly) {
						me.close();
						return;
					}
					
					ERP.MsgBox.confirm("请确认是否取消当前操作？", function(){
						me.close();
					});
				},
				scope : me
			}],
			items : [ {
				region : "center",
				layout : "fit",
				border : 0,
				bodyPadding : 10,
				items : [ me.getGoodsGrid() ]
			}, {
				region : "north",
				id : "editForm",
				layout : {
					type : "table",
					columns : 2
				},
				height : 100,
				bodyPadding : 10,
				border : 0,
				items : [ {
					xtype : "hidden",
					id : "hiddenId",
					name : "id",
					value : entity == null ? null : entity.get("id")
				}, {
					id : "editRef",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "单号",
					xtype : "displayfield",
					value : "<span style='color:red'>保存后自动生成</span>"
				}, {
					id : "editBizDT",
					fieldLabel : "业务日期",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					allowBlank : false,
					blankText : "没有输入业务日期",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					xtype : "datefield",
					format : "Y-m-d",
					value : new Date(),
					name : "bizDT",
					listeners : {
						specialkey : {
							fn : me.onEditBizDTSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "hidden",
					id : "editSupplierId",
					name : "supplierId"
				}, {
					id : "editSupplier",
					colspan : 2,
					width : 430,
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					xtype : "jyerp_supplierfield",
					parentCmp : me,
					fieldLabel : "供应商",
					allowBlank : false,
					blankText : "没有输入供应商",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditSupplierSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "hidden",
					id : "editWarehouseId",
					name : "warehouseId"
				}, {
					id : "editWarehouse",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "入库仓库",
					xtype : "jyerp_warehousefield",
					parentCmp : me,
					fid: "2001",
					allowBlank : false,
					blankText : "没有输入入库仓库",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditWarehouseSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "hidden",
					id : "editBizUserId",
					name : "bizUserId"
				}, {
					id : "editBizUser",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "业务员",
					xtype : "jyerp_userfield",
					parentCmp : me,
					allowBlank : false,
					blankText : "没有输入业务员",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditBizUserSpecialKey,
							scope : me
						}
					}
				} ]
			} ],
			listeners : {
				show : {
					fn : me.onWndShow,
					scope : me
				}
			}
		});

		me.callParent(arguments);
	},
	onWndShow : function() {
		var me = this;

		var el = me.getEl() || Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Purchase/pwBillInfo",
			params : {
				id : Ext.getCmp("hiddenId").getValue()
			},
			method : "POST",
			callback : function(options, success, response) {
				el.unmask();

				if (success) {
					var data = Ext.JSON.decode(response.responseText);

					if (data.ref) {
						Ext.getCmp("editRef").setValue(data.ref);
					}

					Ext.getCmp("editSupplierId").setValue(data.supplierId);
					Ext.getCmp("editSupplier").setValue(data.supplierName);

					Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
					Ext.getCmp("editWarehouse").setValue(data.warehouseName);

					Ext.getCmp("editBizUserId").setValue(data.bizUserId);
					Ext.getCmp("editBizUser").setValue(data.bizUserName);
					if (data.bizDT) {
						Ext.getCmp("editBizDT").setValue(data.bizDT);
					}

					var store = me.getGoodsGrid().getStore();
					store.removeAll();
					if (data.items) {
						store.add(data.items);
					}
					if (store.getCount() == 0) {
						store.add({});
					}
					
					if (data.billStatus && data.billStatus !=0) {
						me.setBillReadonly();
					}
				}
			}
		});
	},
	// private
	onOK : function() {
		var me = this;
		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/Purchase/editPWBill",
			method : "POST",
			params : {
				jsonStr : me.getSaveData()
			},
			callback : function(options, success, response) {
				Ext.getBody().unmask();

				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					if (data.success) {
						ERP.MsgBox.showInfo("成功保存数据", function() {
							me.close();
							me.getParentForm().refreshPWBillGrid(data.id);
						});
					} else {
						ERP.MsgBox.showInfo(data.msg);
					}
				}
			}
		});

	},
	onEditBizDTSpecialKey : function(field, e) {
		if (e.getKey() == e.ENTER) {
			Ext.getCmp("editSupplier").focus();
		}
	},
	onEditSupplierSpecialKey : function(field, e) {
		if (e.getKey() == e.ENTER) {
			Ext.getCmp("editWarehouse").focus();
		}
	},
	onEditWarehouseSpecialKey : function(field, e) {
		if (e.getKey() == e.ENTER) {
			Ext.getCmp("editBizUser").focus();
		}
	},
	onEditBizUserSpecialKey : function(field, e) {
		if (this.__readonly) {
			return;
		}
		
		if (e.getKey() == e.ENTER) {
			var me = this;
			var store = me.getGoodsGrid().getStore();
			if (store.getCount() == 0) {
				store.add({});
			}
			me.getGoodsGrid().focus();
			me.__cellEditing.startEdit(0, 1);
		}
	},
	// SupplierField回调此方法
	__setSupplierInfo : function(data) {
		Ext.getCmp("editSupplierId").setValue(data.id);
	},
	// WarehouseField回调此方法
	__setWarehouseInfo : function(data) {
		Ext.getCmp("editWarehouseId").setValue(data.id);
	},
	// UserField回调此方法
	__setUserInfo : function(data) {
		Ext.getCmp("editBizUserId").setValue(data.id);
	},
	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		Ext.define("ERPPWBillDetail_EditForm", {
			extend : "Ext.data.Model",
			fields : [ "id", "goodsId", "goodsCode", "goodsName", "goodsSpec",
					"unitName", "goodsCount", "goodsMoney", "goodsPrice" ]
		});
		var store = Ext.create("Ext.data.Store", {
			autoLoad : false,
			model : "ERPPWBillDetail_EditForm",
			data : []
		});

		me.__cellEditing = Ext.create("ERP.UX.CellEditing", {
			clicksToEdit : 1,
			listeners : {
				edit : {
					fn : me.cellEditingAfterEdit,
					scope : me
				}
			}
		});

		me.__goodsGrid = Ext.create("Ext.grid.Panel", {
			viewConfig: {
                enableTextSelection: true
            },
			plugins : [ me.__cellEditing ],
			columnLines : true,
			columns : [
					Ext.create("Ext.grid.RowNumberer", {
						text : "序号",
						width : 30
					}),
					{
						header : "商品编码",
						dataIndex : "goodsCode",
						menuDisabled : true,
						sortable : false,
						editor : {
							xtype : "jyerp_goodsfield",
							parentCmp : me
						}
					},
					{
						header : "商品名称",
						dataIndex : "goodsName",
						menuDisabled : true,
						sortable : false,
						width : 200
					},
					{
						header : "规格型号",
						dataIndex : "goodsSpec",
						menuDisabled : true,
						sortable : false,
						width : 200
					},
					{
						header : "采购数量",
						dataIndex : "goodsCount",
						menuDisabled : true,
						sortable : false,
						align : "right",
						width : 100,
						editor : {
							xtype : "numberfield",
							allowDecimals : false,
							hideTrigger : true
						}
					},
					{
						header : "单位",
						dataIndex : "unitName",
						menuDisabled : true,
						sortable : false,
						width : 60
					},
					{
						header : "采购单价",
						dataIndex : "goodsPrice",
						menuDisabled : true,
						sortable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 100,
						editor : {
							xtype : "numberfield",
							hideTrigger : true
						}
					},
					{
						header : "采购金额",
						dataIndex : "goodsMoney",
						menuDisabled : true,
						sortable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 120
					},
					{
						header : "",
						id: "columnActionDelete",
						align : "center",
						menuDisabled : true,
						width : 50,
						xtype : "actioncolumn",
						items : [ {
							icon : ERP.Const.BASE_URL
									+ "Public/Images/icons/delete.png",
							handler : function(grid, row) {
								var store = grid.getStore();
								store.remove(store.getAt(row));
								if (store.getCount() == 0) {
									store.add({});
								}
							},
							scope : me
						} ]
					} ],
			store : store,
			listeners : {
				cellclick: function() {
					return !me.__readonly;
				}
			}
		});

		return me.__goodsGrid;
	},
	__setGoodsInfo : function(data) {
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var goods = item[0];

		goods.set("goodsId", data.id);
		goods.set("goodsCode", data.code);
		goods.set("goodsName", data.name);
		goods.set("unitName", data.unitName);
		goods.set("goodsSpec", data.spec);
	},
	cellEditingAfterEdit : function(editor, e) {
		var me = this;
		
		if (me.__readonly) {
			return;
		}
		
		if (e.colIdx == 6) {
			me.calcMoney();

			var store = me.getGoodsGrid().getStore();
			if (e.rowIdx == store.getCount() - 1) {
				store.add({});
			}
			e.rowIdx += 1;
			me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
			me.__cellEditing.startEdit(e.rowIdx, 1);
		} else if (e.colIdx == 4) {
			me.calcMoney();
		}
	},
	calcMoney : function() {
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var goods = item[0];
		goods.set("goodsMoney", goods.get("goodsCount")
				* goods.get("goodsPrice"));
	},
	getSaveData : function() {
		var result = {
			id : Ext.getCmp("hiddenId").getValue(),
			bizDT : Ext.Date
					.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
			supplierId : Ext.getCmp("editSupplierId").getValue(),
			warehouseId : Ext.getCmp("editWarehouseId").getValue(),
			bizUserId : Ext.getCmp("editBizUserId").getValue(),
			items : []
		};

		var store = this.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
				id : item.get("id"),
				goodsId : item.get("goodsId"),
				goodsCount : item.get("goodsCount"),
				goodsPrice : item.get("goodsPrice")
			});
		}

		return Ext.JSON.encode(result);
	},
	
	setBillReadonly: function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("查看采购入库单");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		Ext.getCmp("editBizDT").setReadOnly(true);
		Ext.getCmp("editSupplier").setReadOnly(true);
		Ext.getCmp("editWarehouse").setReadOnly(true);
		Ext.getCmp("editBizUser").setReadOnly(true);
		Ext.getCmp("columnActionDelete").hide();
	}
});