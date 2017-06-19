// 当仓库需要设置组织机构的时候，编辑仓库所属组织机构的界面
Ext.define("ERP.Warehouse.EditOrgForm", {
	extend : "Ext.window.Window",
	config : {
		parentForm : null,
		warehouseId: null,
		fid: null
	},
	initComponent : function() {
		var me = this;

		var buttons = [];

		buttons.push({
			text : "确定",
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

		Ext.apply(me, {
			title : "添加组织机构",
			modal : true,
			onEsc : Ext.emptyFn,
			width : 650,
			height : 400,
			layout : "fit",
			listeners : {
				show : {
					fn : me.onWndShow,
					scope : me
				},
				close : {
					fn : me.onWndClose,
					scope : me
				}
			},
			items : [me.getOrgTreeGrid()],
			buttons : buttons
		});

		me.callParent(arguments);
	},
	// private
	onOK : function() {
		var me = this;
		var item = me.getOrgTreeGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择组织机构或者人员");
            return;
        }
        var org = item[0];

        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Warehouse/addOrg",
            params: {
            	warehouseId: me.getWarehouseId(),
            	fid: me.getFid(),
            	orgId: org.get("id")
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                    	me.close();
                    } else {
                        ERP.MsgBox.showInfo(data.msg);
                    }
                } else {
                    ERP.MsgBox.showInfo("网络错误");
                }
            }
        });
	},
	onWndClose : function() {
		this.getParentForm().onBillGridSelect();
	},
	onWndShow : function() {
	},
	getOrgTreeGrid: function() {
		var me = this;
		if (me.__treeGrid) {
			return me.__treeGrid;
		}
		
		var modelName = "ERPOrgModel_EditOrgForm";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "text", "fullName", "orgCode", "leaf", "children"]
        });

        var orgStore = Ext.create("Ext.data.TreeStore", {
            model: modelName,
            proxy: {
                type: "ajax",
                url: ERP.Const.BASE_URL + "Home/Warehouse/allOrgs"
            }
        });

        me.__treeGrid = Ext.create("Ext.tree.Panel", {
            store: orgStore,
            rootVisible: false,
            useArrows: true,
            viewConfig: {
                loadMask: true
            },
            columns: {
                defaults: {
                    sortable: false,
                    menuDisabled: true,
                    draggable: false
                },
                items: [{
                        xtype: "treecolumn",
                        text: "名称",
                        dataIndex: "text",
                        width: 220
                    }, {
                        text: "编码",
                        dataIndex: "orgCode",
                        width: 100
                    },{
                    	text: "全名",
                    	dataIndex: "fullName",
                    	flex: 1
                    }]
            }
        });
        
        return me.__treeGrid;
	}
});