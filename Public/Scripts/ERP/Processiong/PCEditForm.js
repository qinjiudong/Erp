// 采购入库单 - 新增或编辑界面
Ext.define("ERP.Purchase.PCEditForm", {
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
		//商品种类
        var goodsType = Ext.regModel('ERPGoodsType', {
            fields: ["id", "name"]
        });
        var goodsTypeStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsType',
            data: [{"id":"0","name":"正常商品"},{"id":"1", "name":"赠品"}]
        });
        var goodsTypeData = ['正常商品', '赠品'];
        me.goodsTypeData = goodsTypeData;
        me.goodsTypeStore = goodsTypeStore;
		Ext.apply(me, {
			title : entity == null ? "新建加工单" : "编辑加工单",
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
				title:'待加工商品',
				height:50,
				items : [ me.getSourceGoodsGrid() ]
			},
			{
				region : "south",
				layout : "fit",
				border : 0,
				bodyPadding : 10,
				title:'成品商品',
				height:300,
				items : [ me.getGoodsGrid() ]
			},
			 {
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
					id : "editOutWarehouseId",
					name : "outWarehouseId"
				}, {
					id : "editOutWarehouse",
					colspan : 2,
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					xtype : "jyerp_warehousefield",
					parentCmp : me,
					fieldLabel : "出库仓库",
					allowBlank : false,
					blankText : "没有输入出库",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditSupplierSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "hidden",
					id : "editInWarehouseId",
					name : "inWarehouseId"
				}, {
					id : "editInWarehouse",
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
				}, 
				/*
				{
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
				}
				*/
				 ]
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
			url : ERP.Const.BASE_URL + "Home/Processiong/pcBillInfo",
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

					//Ext.getCmp("editSupplierId").setValue(data.supplierId);
					//Ext.getCmp("editSupplier").setValue(data.supplierName);

					Ext.getCmp("editOutWarehouseId").setValue(data.outWarehouseId);
					Ext.getCmp("editOutWarehouse").setValue(data.outWarehouseName);
					Ext.getCmp("editInWarehouseId").setValue(data.inWarehouseId);
					Ext.getCmp("editInWarehouse").setValue(data.inWarehouseName);
					Ext.getCmp("editBizUserId").setValue(data.bizUserId);
					//Ext.getCmp("editBizUser").setValue(data.bizUserName);
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
					var sourceStore = me.getSourceGoodsGrid().getStore();
					sourceStore.removeAll();
					if (data.source_items) {
						sourceStore.add(data.source_items);
					}
					if (sourceStore.getCount() == 0) {
						sourceStore.add({});
					}
					if (data.billStatus && data.billStatus !=0 && data.billStatus !=3) {
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
			url : ERP.Const.BASE_URL + "Home/Processiong/editPCBill",
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
							me.getParentForm().refreshPCBillGrid(data.id);
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
	getSourceGoodsGrid: function(){
		var me = this;
		if (me.__sourceGoodsGrid) {
			return me.__sourceGoodsGrid;
		}
		Ext.define("ERPPWBillDetail_EditForm", {
			extend : "Ext.data.Model",
			fields : [ "id", "goodsId", "goodsCode", "goodsBarCode", "goodsName", "goodsSpec",
					"unitName", "goodsCount", "goodsMoney", "goodsPrice", "goodsType", "goodsCount", "goodsCountAfter", "goodsCountAfterActual" ]
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
		
		me.__sourceGoodsGrid = Ext.create("Ext.grid.Panel", {
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
							xtype : "jyerp_goods_pc_field",
							parentCmp : me,
							listeners:{
								focus:function(){
									//如果获得焦点，则去除成品grid的选中状态防止误选
									var item = me.getGoodsGrid().getSelectionModel().getSelection();
									if (item == null || item.length != 1) {
										
									} else {
										me.getGoodsGrid().getSelectionModel().deselect(item);
										me.__currentGrid = me.__sourceGoodsGrid;
									}
								}
							}
						}
					},
					{
						header : "商品条码",
						dataIndex : "goodsBarCode",
						menuDisabled : true,
						sortable : false,
						width : 200
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
					/*
					{
						header : "数量",
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
					*/
					{
						header : "单位",
						dataIndex : "unitName",
						menuDisabled : true,
						sortable : false,
						width : 60
					},
					
					{
						header : "加工前库存",
						dataIndex : "goodsCount",
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
						header : "加工后库存",
						dataIndex : "goodsCountAfter",
						menuDisabled : true,
						sortable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 120,
						editor : {
							xtype : "numberfield",
							hideTrigger : true
						}
					},
					{
						header : "加工后实际称重库存",
						dataIndex : "goodsCountAfterActual",
						menuDisabled : true,
						sortable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 120,
						editor : {
							xtype : "numberfield",
							hideTrigger : true
						}
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

		return me.__sourceGoodsGrid;
	},
	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		Ext.define("ERPPWBillDetail_EditForm", {
			extend : "Ext.data.Model",
			fields : [ "id", "goodsId", "goodsBarCode", "goodsCode", "goodsName", "goodsSpec",
					"unitName", "goodsCount", "goodsMoney", "goodsPrice", "goodsType" ]
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
						header : "散装条码",
						dataIndex : "goodsBarCode",
						menuDisabled : true,
						sortable : false,
						align : "right",
						width : 150,
						editor : {
							xtype : "textfield",
							hideTrigger : true,
							listeners:{
								change:function(a,b,c){
									var barCode = this.value;
									if(barCode && barCode.length == 18){
										var item = me.getGoodsGrid().getSelectionModel().getSelection();
										var goods = item[0];
										var item_source = me.getSourceGoodsGrid().getStore().getAt(0);
										var source_goods = item_source;
										//根据条码信息获取到重量等信息
										var barCodeArray = barCode.split("");
										var len = barCode.length;
										var date = barCode.substr(-2,1);
										var weight = barCodeArray[len-6]+barCodeArray[len-5]+"."+barCodeArray[len-4]+barCodeArray[len-3]+barCodeArray[len-2];
										weight = parseFloat(weight);
										var price = barCodeArray[len-11]+barCodeArray[len-10]+barCodeArray[len-9]+"."+barCodeArray[len-8]+barCodeArray[len-7];
										price = parseFloat(price);
										var code = barCode.substr(0,len - 11);
										goods.set("goodsCode", code);
										//alert(code);
										//alert(me.__sourceBarCode);
										if(code != me.__sourceBarCode){
											ERP.MsgBox.showInfo("条码无法对应");
											return false;
										}
										goods.set("goodsCount", weight);
										goods.set("goodsMoney", price);
										goods.set("goodsId", source_goods.get("goodsId"));
										me.__calSourceGoods();
									}
									if(barCode && barCode.length != 18){
										//ERP.MsgBox.showInfo("条码格式不正确");
										//this.value = "";
									}
									
								}
							}
						}
					},
					{
						header : "商品条码",
						dataIndex : "goodsCode",
						menuDisabled : true,
						sortable : false,
						editor : {
							xtype : "jyerp_goodsfield",
							parentCmp : me,
							listeners:{
								focus:function(){
									//如果获得焦点，则去除材料grid的选中
									var item = me.getSourceGoodsGrid().getSelectionModel().getSelection();
									if (item == null || item.length != 1) {
										
									} else {
										me.getSourceGoodsGrid().getSelectionModel().deselect(item);
										me.__currentGrid = me.__goodsGrid;
									}

								}
							}
						}
					},
					/*
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
					*/
					{
						header : "数量(重量)",
						dataIndex : "goodsCount",
						menuDisabled : true,
						sortable : false,
						align : "right",
						width : 100,
						xtype:"numbercolumn",
						editor : {
							xtype : "numberfield",
							allowDecimals : true,
							hideTrigger : true,
							listeners:{
								blur:function(){
									me.__calSourceGoods();
								}
							}
						}
					},
					{
						header : "价格",
						dataIndex : "goodsMoney",
						menuDisabled : true,
						sortable : false,
						align : "right",
						width : 100,
						xtype:"numbercolumn",
						editor : {
							xtype : "numberfield",
							allowDecimals : true,
							hideTrigger : true
						}
					},
					/*
					{
						header : "单位",
						dataIndex : "unitName",
						menuDisabled : true,
						sortable : false,
						width : 60
					},
					*/
					/*
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
					*/
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
								me.__calSourceGoods();
								if (store.getCount() == 0) {
									store.add({});
								}

							},
							scope : me
						},
						{
                            icon : ERP.Const.BASE_URL
                                    + "Public/Images/icons/add.png",
                            handler : function(grid, row) {
                                var store = grid.getStore();
                                me.__addGoodsNewRow();
                                /*
                                if (store.getCount() >= 0) {
                                    store.add({});
                                }
                                */
                            },
                            scope : me
                        }
						 ]
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
	__addGoodsNewRow: function(e){
        var me = this;
        var store = this.getGoodsGrid().getStore();
        var i = store.getCount()-1;
        var code = store.getAt(i).get("goodsCode");
        if(code){
            store.add({});
        }
    },
    __calSourceGoods:function(){
    	var me = this;
        var store = this.getSourceGoodsGrid().getStore();
        var i = store.getCount()-1;
        var goodsCount = store.getAt(i).get("goodsCount");
        var goodsStore = this.getGoodsGrid().getStore();
        var totalCount = 0;
        for(var j = 0 ; j < goodsStore.getCount() ; j++){
       		totalCount+= parseFloat(goodsStore.getAt(j).get("goodsCount"));
        }
        store.getAt(i).set("goodsCountAfter", goodsCount - totalCount);
        store.getAt(i).set("goodsCountAfterActual", goodsCount - totalCount);
        if(goodsCount - totalCount < 0){
        	ERP.MsgBox.showInfo("该库存已经不足");
        }
    },
	__setGoodsInfo : function(data) {
		//如果没有选择供应商，则无法添加商品数据
		if(Ext.getCmp("editSupplierId") && Ext.getCmp("editSupplierId").getValue() == ""){
			//ERP.MsgBox.showInfo("请先选择一个供应商!");
			//return;
		}
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			item = me.getSourceGoodsGrid().getSelectionModel().getSelection();
			if (item == null || item.length != 1) {
				return;
			} else {
				me.__currentGrid = me.getSourceGoodsGrid();
				var item_product = me.getGoodsGrid().getStore().getAt(0);
				if(item_product){
					if(item_product.get("goodsId") == data.id){
						return false;
					}
				}

			}
		} else {
			me.__currentGrid = me.getGoodsGrid();
			var item_source = me.getSourceGoodsGrid().getStore().getAt(0);
			if(item_source){
				if(item_source.get("goodsId") == data.id){
					return false;
				}
			}
		}
		var goods = item[0];

		goods.set("goodsId", data.id);
		goods.set("goodsCode", data.code);
		goods.set("goodsName", data.name);
		goods.set("unitName", data.unitName);
		goods.set("goodsSpec", data.spec);
	},

	__setSourceGoodsInfo:function(data){
		//如果没有选择供应商，则无法添加商品数据
		if(Ext.getCmp("editSupplierId") && Ext.getCmp("editSupplierId").getValue() == ""){
			//ERP.MsgBox.showInfo("请先选择一个供应商!");
			//return;
		}
		var me = this;
		var item = me.getSourceGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return false;
		} else {
			
		}
		var goods = item[0];

		goods.set("goodsId", data.id);
		goods.set("goodsCode", data.code);
		goods.set("goodsBarCode", data.barCode);
		me.__sourceBarCode = data.barCode;
		goods.set("goodsName", data.name);
		goods.set("unitName", data.unitName);
		goods.set("goodsSpec", data.spec);
		goods.set("goodsCount", data.goodsCount);
		goods.set("WarehouseName", data.goodsCount);
	},
	cellEditingAfterEdit : function(editor, e) {
		var me = this;
		
		if (me.__readonly) {
			return;
		}
		if(e.colIdx == 3){
			me.__calSourceGoods();
		}
		//只允许添加一个
		/*
		if(e.colIdx == 4 && me.__currentGrid){
			var store = me.__currentGrid.getStore();
			if (e.rowIdx == store.getCount() - 1) {
				store.add({});
			}
			e.rowIdx += 1;
			me.__currentGrid.getSelectionModel().select(e.rowIdx);
			me.__cellEditing.startEdit(e.rowIdx, 1);
		}
		*/
		/*
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
		} else if (e.colIdx == 8){
			me.calcMoney();
		}
		*/
	},
	calcMoney : function() {
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var goods = item[0];
		var goodsType = goods.get("goodsType");
		if(goodsType == 0 || goodsType == ""){

		} else {
			goods.set("goodsPrice",0);
		}
		goods.set("goodsMoney", goods.get("goodsCount")
				* goods.get("goodsPrice"));
	},
	getSaveData : function() {
		var result = {
			id : Ext.getCmp("hiddenId").getValue(),
			bizDT : Ext.Date
					.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
			//supplierId : Ext.getCmp("editSupplierId").getValue(),
			warehouseId : Ext.getCmp("editOutWarehouseId").getValue(),
			inWarehouseId : Ext.getCmp("editInWarehouseId").getValue(),
			outWarehouseId : Ext.getCmp("editOutWarehouseId").getValue(),
			bizUserId : Ext.getCmp("editBizUserId").getValue(),
			items : [],
			source_items:[]
		};

		var store = this.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
				id : item.get("id"),
				goodsBarCode : item.get("goodsBarCode"),
				goodsId : item.get("goodsId"),
				goodsCount : item.get("goodsCount"),
				goodsMoney : item.get("goodsMoney"),
				//goodsPrice : item.get("goodsPrice"),
			});
		}
		var source_store = this.getSourceGoodsGrid().getStore();
		for (var i = 0; i < source_store.getCount(); i++){
			var item = source_store.getAt(i);
			result.source_items.push({
				id : item.get("id"),
				goodsId : item.get("goodsId"),
				goodsCount : item.get("goodsCount"),
				goodsCountAfter : item.get("goodsCountAfter"),
				goodsCountAfterActual : item.get("goodsCountAfterActual"),
				//goodsPrice : item.get("goodsPrice"),
			});
		}

		return Ext.JSON.encode(result);
	},
	
	setBillReadonly: function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("查看加工单");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		Ext.getCmp("editBizDT").setReadOnly(true);
		Ext.getCmp("editSupplier").setReadOnly(true);
		Ext.getCmp("editWarehouse").setReadOnly(true);
		//Ext.getCmp("editBizUser").setReadOnly(true);
		Ext.getCmp("columnActionDelete").hide();
	}
});