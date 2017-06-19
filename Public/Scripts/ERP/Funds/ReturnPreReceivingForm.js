// 预收款管理 - 退还预收款
Ext.define("ERP.Funds.ReturnPreReceivingForm", {
	extend : "Ext.window.Window",

	config : {
		parentForm : null
	},

	initComponent : function() {
		var me = this;
		Ext.apply(me, {
			title : "退还预收款",
			modal : true,
			onEsc : Ext.emptyFn,
			width : 400,
			height : 200,
			layout : "fit",
			defaultFocus : "editCustomer",
			listeners : {
				show : {
					fn : me.onWndShow,
					scope : me
				}
			},
			items : [ {
				id : "editForm",
				xtype : "form",
				layout : "form",
				height : "100%",
				bodyPadding : 5,
				defaultType : 'textfield',
				fieldDefaults : {
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side'
				},
				items : [
				{
					id: "editCustomerId",
					xtype: "hidden",
					name: "customerId"
				},
				{
					id : "editCustomer",
					fieldLabel : "客户",
					xtype : "jyerp_customerfield",
					allowBlank : false,
					blankText : "没有输入客户",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditCustomerSpecialKey,
							scope : me
						}
					}
				},
				{
					id : "editBizDT",
					fieldLabel : "退款日期",
                    allowBlank: false,
                    blankText: "没有输入退款日期",
                    beforeLabelTextTpl: ERP.Const.REQUIRED,
                    xtype: "datefield",
                    format: "Y-m-d",
                    value: new Date(),
                    name: "bizDT",
					listeners : {
						specialkey : {
							fn : me.onEditBizDTSpecialKey,
							scope : me
						}
					}
				}, {
					fieldLabel : "退款金额",
					allowBlank : false,
					blankText : "没有输入退款金额",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					xtype : "numberfield",
					hideTrigger : true,
					name : "outMoney",
					id : "editOutMoney",
					listeners : {
						specialkey : {
							fn : me.onEditOutMoneySpecialKey,
							scope : me
						}
					}
				}, {
					id: "editBizUserId",
					xtype: "hidden",
					name: "bizUserId"
				}, {
					id : "editBizUser",
					fieldLabel : "收款人",
					xtype : "jyerp_userfield",
					allowBlank : false,
					blankText : "没有输入收款人",
					beforeLabelTextTpl : ERP.Const.REQUIRED,
					listeners : {
						specialkey : {
							fn : me.onEditBizUserSpecialKey,
							scope : me
						}
					}
				} ],
				buttons : [ {
					text : "保存",
					iconCls : "ERP-button-ok",
					formBind : true,
					handler : me.onOK,
					scope : me
				}, {
					text : "取消",
					handler : function() {
						me.close();
					},
					scope : me
				} ]
			} ]
		});

		me.callParent(arguments);
	},

	onWndShow : function() {
    	var me = this;
    	var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Funds/returnPreReceivingInfo",
            params: {
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    Ext.getCmp("editBizUserId").setValue(data.bizUserId);
                    Ext.getCmp("editBizUser").setValue(data.bizUserName);
                    Ext.getCmp("editBizUser").setIdValue(data.bizUserId);
                } else {
                    ERP.MsgBox.showInfo("网络错误")
                }
            }
        });
	},

	// private
	onOK : function() {
		var me = this;
        Ext.getCmp("editBizUserId").setValue(Ext.getCmp("editBizUser").getIdValue());
        Ext.getCmp("editCustomerId").setValue(Ext.getCmp("editCustomer").getIdValue());

        var f = Ext.getCmp("editForm");
		var el = f.getEl();
		el.mask(ERP.Const.SAVING);
		f.submit({
			url : ERP.Const.BASE_URL + "Home/Funds/returnPreReceiving",
			method : "POST",
			success : function(form, action) {
				el.unmask();
				
				me.close();
				
				me.getParentForm().onQuery();
			},
			failure : function(form, action) {
				el.unmask();
				ERP.MsgBox.showInfo(action.result.msg, function() {
					Ext.getCmp("editBizDT").focus();
				});
			}
		});
	},
	
    onEditCustomerSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editBizDT").focus();
        }
    },

    onEditBizDTSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editOutMoney").focus();
        }
    },
    
    onEditOutMoneySpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editBizUser").focus();
        }
    },
    
    onEditBizUserSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                var me = this;
                ERP.MsgBox.confirm("请确认是否录入退款记录?", function () {
                    me.onOK();
                });
            }
        }
    }
});