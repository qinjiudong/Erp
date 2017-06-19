// 业务设置 - 编辑设置项目
Ext.define("ERP.BizConfig.EditForm", {
	extend : "Ext.window.Window",
	config : {
		parentForm : null
	},
	initComponent : function() {
		var me = this;

		var buttons = [];

		buttons.push({
			text : "保存",
			formBind : true,
			iconCls : "ERP-button-ok",
			handler : function() {
				me.onOK();
			},
			scope : me
		}, {
			text : "取消",
			handler : function() {
				me.close();
			},
			scope : me
		});
		
		var modelName = "PSIWarehouse";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });

        var storePW = Ext.create("Ext.data.Store", {
            model: modelName,
            autoLoad: false,
            fields : [ "id", "name" ],
            data: []
        });
        me.__storePW = storePW;
        var storeWS = Ext.create("Ext.data.Store", {
            model: modelName,
            autoLoad: false,
            fields : [ "id", "name" ],
            data: []
        });
        me.__storeWS = storeWS;
        var storeUser = Ext.create("Ext.data.Store", {
            model: modelName,
            autoLoad: false,
            fields : [ "id", "name" ],
            data: []
        });
        me.__storeUser = storeUser;
		Ext.apply(me, {
			title : "业务设置",
			modal : true,
			onEsc : Ext.emptyFn,
			width : 400,
			height : 350,
			layout : "fit",
			items : [ {
				xtype : "tabpanel",
				bodyPadding : 5,
				items : [
				         {
				        	 title: "公司",
				        	 layout: "form",
				        	 iconCls: "ERP-fid2008",
				        	 items: [{
				        		 id: "editName9000-01",
				        		 xtype: "displayfield"
				        	 },{
				        		 id: "editValue9000-01",
				        		 xtype: "textfield"
				        	 },{
				        		 id: "editName9000-02",
				        		 xtype: "displayfield"
				        	 },{
				        		 id: "editValue9000-02",
				        		 xtype: "textfield"
				        	 },{
				        		 id: "editName9000-03",
				        		 xtype: "displayfield"
				        	 },{
				        		 id: "editValue9000-03",
				        		 xtype: "textfield"
				        	 },{
				        		 id: "editName9000-04",
				        		 xtype: "displayfield"
				        	 },{
				        		 id: "editValue9000-04",
				        		 xtype: "textfield"
				        	 },{
				        		 id: "editName9000-05",
				        		 xtype: "displayfield"
				        	 },{
				        		 id: "editValue9000-05",
				        		 xtype: "textfield"
				        	 }
				        	 ]
				         },
				         {
				        	 title: "采购",
				        	 layout: "form",
				        	 iconCls: "ERP-fid2001",
				        	 items: [{
									id : "editName2001-01",
									xtype : "displayfield"
								}, {
									id : "editValue2001-01",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									displayField: "name",
									store : storePW,
									name : "value2001-01"
								},{
									id : "editName2001-02",
									xtype : "displayfield"
								}, {
									id : "editValue2001-02",
									xtype: "combo",
									queryMode : "local",
									editable : false,
									parentCmp: me,
									valueField : "id",
									displayField: "name",
									store : storeUser,
									name : "value2001-02",
									
								}
				        	 ]
				         },
				         {
				        	 title: "销售",
				        	 layout: "form",
				        	 iconCls: "ERP-fid2002",
				        	 items: [{
									id : "editName2002-02",
									xtype : "displayfield"
								}, {
									id : "editValue2002-02",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									displayField: "name",
									store : storeWS,
									name : "value2002-02"
								}, {
									id : "editName2002-01",
									xtype : "displayfield"
								}, {
									id : "editValue2002-01",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									store : Ext.create("Ext.data.ArrayStore", {
										fields : [ "id", "text" ],
										data : [ [ "0", "不允许编辑销售单价" ], [ "1", "允许编辑销售单价" ] ]
									}),
									name : "value2002-01"
								},
								{
									id : "editName2002-03",
									xtype : "displayfield"
								},
								{
									id : "editValue2002-03",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									store : Ext.create("Ext.data.ArrayStore", {
										fields : [ "id", "text" ],
										data : [ [ "0", "不发送" ], [ "1", "发送" ] ]
									}),
									name : "value2002-03"
								},
								{
									id : "editName2002-04",
									xtype : "displayfield"
								},
								{
									id : "editValue2002-04",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									store : Ext.create("Ext.data.ArrayStore", {
										fields : [ "id", "text" ],
										data : [ [ "0", "不发送" ], [ "1", "发送" ] ]
									}),
									name : "value2002-04"
								}
				        	 ]
				         },
				         {
				        	 title: "库存",
				        	 layout: "form",
				        	 iconCls: "ERP-fid1003",
				        	 items: [{
									id : "editName1003-01",
									xtype : "displayfield"
								}, {
									id : "editValue1003-01",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									store : Ext.create("Ext.data.ArrayStore", {
										fields : [ "id", "text" ],
										data : [ [ "0", "仓库不需指定组织机构" ], [ "1", "仓库需指定组织机构" ] ]
									}),
									name : "value1003-01"
								}
				        	 ]
				         },
				         {
				        	 title: "信息配置",
				        	 layout: "form",
				        	 iconCls: "ERP-fid1003",
				        	 items: [
				        	 	{
									id : "editName10000-01",
									xtype : "displayfield"
								}, 
								{
									id : "editValue10000-01",
									xtype: "textfield",
									name : "value10000-01"
								},
								{
									id : "editName10000-02",
									xtype : "displayfield"
								}, 
								{
									id : "editValue10000-02",
									xtype: "textfield",
									name : "value10000-02"
								},
								{
									id : "editName10000-03",
									xtype : "displayfield"
								}, 
								{
									id : "editValue10000-03",
									xtype: "textfield",
									name : "value10000-03"
								},
				        	 ]
				         }
				 ],
				buttons : buttons
			} ],
			listeners : {
				close : {
					fn : me.onWndClose,
					scope : me
				},
				show : {
					fn : me.onWndShow,
					scope : me
				}
			}
		});

		me.callParent(arguments);
	},
	
	getSaveData: function() {
		var result = {
				'value9000-01': Ext.getCmp("editValue9000-01").getValue(),
				'value9000-02': Ext.getCmp("editValue9000-02").getValue(),
				'value9000-03': Ext.getCmp("editValue9000-03").getValue(),
				'value9000-04': Ext.getCmp("editValue9000-04").getValue(),
				'value9000-05': Ext.getCmp("editValue9000-05").getValue(),
				'value1003-01': Ext.getCmp("editValue1003-01").getValue(),
				'value2001-01': Ext.getCmp("editValue2001-01").getValue(),
				'value2002-01': Ext.getCmp("editValue2002-01").getValue(),
				'value2002-02': Ext.getCmp("editValue2002-02").getValue(),
				'value2001-02': Ext.getCmp("editValue2001-02").getValue(),
				'value2002-03': Ext.getCmp("editValue2002-03").getValue(),
				'value2002-04': Ext.getCmp("editValue2002-04").getValue(),
				'value10000-01': Ext.getCmp("editValue10000-01").getValue(),
				'value10000-02': Ext.getCmp("editValue10000-02").getValue(),
				'value10000-03': Ext.getCmp("editValue10000-03").getValue(),
		};
		
		return result;
	},
	
	onOK : function(thenAdd) {
		var me = this;
        Ext.getBody().mask("正在保存中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/BizConfig/edit",
            method: "POST",
            params: me.getSaveData(),
            callback: function (options, success, response) {
                Ext.getBody().unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                    	me.__saved = true;
                        ERP.MsgBox.showInfo("成功保存数据", function () {
                            me.close();
                        });
                    } else {
                        ERP.MsgBox.showInfo(data.msg);
                    }
                }
            }
        });
	},

	onWndClose : function() {
		var me = this;
		if (me.__saved) {
			me.getParentForm().refreshGrid();
		}
	},
	
	onWndShow : function() {
		var me = this;
		me.__saved = false;

		var el = me.getEl() || Ext.getBody();
		el.mask(ERP.Const.LOADING);
		Ext.Ajax.request({
			url : ERP.Const.BASE_URL + "Home/BizConfig/allConfigsWithExtData",
			method : "POST",
			callback : function(options, success, response) {
				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					me.__storePW.add(data.extData.warehouse);
					me.__storeWS.add(data.extData.warehouse);

					me.__storeUser.add(data.extData.user);
					for (var i = 0; i < data.dataList.length; i++) {
						var item = data.dataList[i];
						var editName = Ext.getCmp("editName" + item.id);
						if (editName) {
							editName.setValue(item.name);
						}
						var editValue = Ext.getCmp("editValue" + item.id);
						if (editValue) {
							editValue.setValue(item.value);
						}
					}
				} else {
					ERP.MsgBox.showInfo("网络错误");
				}

				el.unmask();
			}
		});
	}
});