// 客户资料 - 主界面
Ext.define("ERP.Customer.MainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    initComponent: function () {
        var me = this;
        Ext.define("ERPCustomerCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", {name: "cnt", type: "int"}]
        });

        var categoryGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "客户分类",
            features: [{ftype: "summary"}],
            forceFit: true,
            columnLines: true,
            columns: [
                {header: "类别编码", dataIndex: "code", width: 60, menuDisabled: true, sortable: false},
                {header: "类别", dataIndex: "name", flex: 1, menuDisabled: true, sortable: false,
                    summaryRenderer: function () {
                        return "客户个数合计";
                    }},
                {header: "客户个数", dataIndex: "cnt", width: 80, menuDisabled: true, sortable: false,
                    summaryType: "sum", align: "right"}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPCustomerCategory",
                autoLoad: false,
                data: []
            }),
            listeners: {
                select: {
                    fn: me.onCategoryGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditCategory,
                    scope: me
                }
            }
        });
        me.categoryGrid = categoryGrid;

        Ext.define("ERPCustomer", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "contact01", "tel01", "mobile01", "qq01",
                "contact02", "tel02", "mobile02", "qq02", "categoryId", "initReceivables",
                "initReceivablesDT", "address", "addressReceipt"]
        });

        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPCustomer",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Customer/customerList",
                reader: {
                    root: 'customerList',
                    totalProperty: 'totalCount'
                }
            },
            listeners: {
                beforeload: {
                    fn: function () {
                    	store.proxy.extraParams = me.getQueryParam();
                    },
                    scope: me
                },
                load: {
                    fn: function (e, records, successful) {
                        if (successful) {
                            me.refreshCategoryCount();
                            me.gotoCustomerGridRecord(me.__lastId);
                        }
                    },
                    scope: me
                }
            }
        });

        var customerGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "客户列表",
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "客户编码", dataIndex: "code", menuDisabled: true, sortable: false},
                {header: "客户名称", dataIndex: "name", menuDisabled: true, sortable: false, width: 300},
                {header: "地址", dataIndex: "address", menuDisabled: true, sortable: false, width: 300},
                {header: "联系人", dataIndex: "contact01", menuDisabled: true, sortable: false},
                {header: "手机", dataIndex: "mobile01", menuDisabled: true, sortable: false},
                {header: "固话", dataIndex: "tel01", menuDisabled: true, sortable: false},
                {header: "QQ", dataIndex: "qq01", menuDisabled: true, sortable: false},
                {header: "备用联系人", dataIndex: "contact02", menuDisabled: true, sortable: false},
                {header: "备用联系人手机", dataIndex: "mobile02", menuDisabled: true, sortable: false},
                {header: "备用联系人固话", dataIndex: "tel02", menuDisabled: true, sortable: false},
                {header: "备用联系人QQ", dataIndex: "qq02", menuDisabled: true, sortable: false},
                {header: "收货地址", dataIndex: "addressReceipt", menuDisabled: true, sortable: false, width: 300},
                {header: "应收期初余额", dataIndex: "initReceivables", align: "right", xtype: "numbercolumn", menuDisabled: true, sortable: false},
                {header: "应收期初余额日期", dataIndex: "initReceivablesDT", menuDisabled: true, sortable: false}
            ],
            store: store,
            bbar: [{
                    id: "pagingToolbar",
                    border: 0,
                    xtype: "pagingtoolbar",
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
                                Ext.getCmp("pagingToolbar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }],
            listeners: {
                itemdblclick: {
                    fn: me.onEditCustomer,
                    scope: me
                }
            }
        });

        me.customerGrid = customerGrid;

        Ext.apply(me, {
            tbar: [
                {text: "新增客户分类", iconCls: "ERP-button-add", handler: me.onAddCategory, scope: me},
                {text: "编辑客户分类", iconCls: "ERP-button-edit", handler: me.onEditCategory, scope: me},
                {text: "删除客户分类", iconCls: "ERP-button-delete", handler: me.onDeleteCategory, scope: me}, "-",
                {text: "新增客户", iconCls: "ERP-button-add-detail", handler: me.onAddCustomer, scope: me},
                {text: "修改客户", iconCls: "ERP-button-edit-detail", handler: me.onEditCustomer, scope: me},
                {text: "删除客户", iconCls: "ERP-button-delete-detail", handler: me.onDeleteCustomer, scope: me}, "-",
                {
                    text: "从电商同步用户",
                    iconCls: "ERP-button-refresh",
                    handler: function () {
                        me.synUsers();
                    }
                },
                "-",
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            layout: "border",
            items: [{
            	region: "north", height: 90, border: 0,
            	collajyerpble: true,
            	title: "查询条件",
            	layout : {
					type : "table",
					columns : 4
				},
            	items: [{
            		id: "editQueryCode",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "客户编码",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryName",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "客户名称",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryAddress",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "地址",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryContact",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "联系人",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryMobile",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "手机",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryTel",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "固话",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					id: "editQueryQQ",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "QQ",
					margin: "5, 0, 0, 0",
					xtype : "textfield",
					listeners: {
                        specialkey: {
                            fn: me.onLastQueryEditSpecialKey,
                            scope: me
                        }
                    }
				},{
					xtype: "container",
					items: [{
						xtype: "button",
						text: "查询",
						width: 100,
						iconCls: "ERP-button-refresh",
						margin: "5, 0, 0, 20",
						handler: me.onQuery,
						scope: me
					},{
						xtype: "button",
						text: "清空查询条件",
						width: 100,
						iconCls: "ERP-button-cancel",
						margin: "5, 0, 0, 5",
						handler: me.onClearQuery,
						scope: me
					}]
				}]
            }, {
            	region: "center", layout: "border",
            	items: [{
                    region: "center", xtype: "panel", layout: "fit", border: 0,
                    items: [customerGrid]
                },{
                    xtype: "panel",
                    region: "west",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [categoryGrid]
                }]}
            ]
        });

        me.callParent(arguments);
        
        me.__queryEditNameList = ["editQueryCode", "editQueryName", "editQueryAddress", "editQueryContact", "editQueryMobile", 
              	                "editQueryTel", "editQueryQQ"];

        me.freshCategoryGrid();
    },
    onAddCategory: function () {
        var form = Ext.create("ERP.Customer.CategoryEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditCategory: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的客户分类");
            return;
        }

        var category = item[0];

        var form = Ext.create("ERP.Customer.CategoryEditForm", {
            parentForm: this,
            entity: category
        });

        form.show();
    },
    onDeleteCategory: function () {
        var me = this;
        var item = me.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的客户分类");
            return;
        }

        var category = item[0];

        var store = me.categoryGrid.getStore();
        var index = store.findExact("id", category.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }


        var info = "请确认是否删除客户分类: <span style='color:red'>" + category.get("name") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Customer/deleteCategory",
                method: "POST",
                params: {id: category.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshCategoryGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            window.location.reload();
                        });
                    }
                }

            });
        });
    },
    freshCategoryGrid: function (id) {
    	var me = this;
        var grid = me.categoryGrid;
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Customer/categoryList",
            method: "POST",
            params: me.getQueryParam(),
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (store.getCount() > 0) {
                        if (id) {
                            var r = store.findExact("id", id);
                            if (r != -1) {
                                grid.getSelectionModel().select(r);
                            }
                        } else {
                            grid.getSelectionModel().select(0);
                        }
                    }
                }

                el.unmask();
            }
        });
    },
    freshCustomerGrid: function (id) {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            var grid = this.customerGrid;
            grid.setTitle("客户列表");
            return;
        }

        var category = item[0];

        var grid = this.customerGrid;
        grid.setTitle("属于分类 [" + category.get("name") + "] 的客户");

        this.__lastId = id;
        Ext.getCmp("pagingToolbar").doRefresh()
    },
    // private
    onCategoryGridSelect: function () {
        this.freshCustomerGrid();
    },
    onAddCustomer: function () {
        if (this.categoryGrid.getStore().getCount() == 0) {
            ERP.MsgBox.showInfo("没有客户分类，请先新增客户分类");
            return;
        }

        var form = Ext.create("ERP.Customer.CustomerEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditCustomer: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择客户分类");
            return;
        }
        var category = item[0];

        var item = this.customerGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的客户");
            return;
        }

        var customer = item[0];
        customer.set("categoryId", category.get("id"));
        var form = Ext.create("ERP.Customer.CustomerEditForm", {
            parentForm: this,
            entity: customer
        });

        form.show();
    },
    onDeleteCustomer: function () {
        var me = this;
        var item = me.customerGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的客户");
            return;
        }

        var customer = item[0];

        var store = me.customerGrid.getStore();
        var index = store.findExact("id", customer.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }


        var info = "请确认是否删除客户: <span style='color:red'>" + customer.get("name") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Customer/deleteCustomer",
                method: "POST",
                params: {id: customer.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshCustomerGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    }
                }

            });
        });
    },
    gotoCustomerGridRecord: function (id) {
        var me = this;
        var grid = me.customerGrid;
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
    refreshCategoryCount: function() {
        var me = this;
        var item = me.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }

        var category = item[0];
        category.set("cnt", me.customerGrid.getStore().getTotalCount());
        me.categoryGrid.getStore().commitChanges();
    },
    
    onQueryEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
            var me = this;
            var id = field.getId();
            for (var i = 0; i < me.__queryEditNameList.length - 1; i++) {
                var editorId = me.__queryEditNameList[i];
                if (id === editorId) {
                    var edit = Ext.getCmp(me.__queryEditNameList[i + 1]);
                    edit.focus();
                    edit.setValue(edit.getValue());
                }
            }
        }
    },
    
    onLastQueryEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
        	this.onQuery();
        }
    },
    
    getQueryParam: function() {
    	var me = this;
        var item = me.categoryGrid.getSelectionModel().getSelection();
        var categoryId;
        if (item == null || item.length != 1) {
            categoryId = null;
        } else {
        	categoryId = item[0].get("id");	
        }

        var result = {
        	categoryId: categoryId
        };
        
        var code = Ext.getCmp("editQueryCode").getValue();
        if (code) {
        	result.code = code;
        }
        
        var address = Ext.getCmp("editQueryAddress").getValue();
        if (address) {
        	result.address = address;
        }
        
        var name = Ext.getCmp("editQueryName").getValue();
        if (name) {
        	result.name = name;
        }
        
        var contact = Ext.getCmp("editQueryContact").getValue();
        if (contact) {
        	result.contact = contact;
        }
        
        var mobile = Ext.getCmp("editQueryMobile").getValue();
        if (mobile) {
        	result.mobile = mobile;
        }
        
        var tel = Ext.getCmp("editQueryTel").getValue();
        if (tel) {
        	result.tel = tel;
        }
        
        var qq = Ext.getCmp("editQueryQQ").getValue();
        if (qq) {
        	result.qq = qq;
        }
        
        return result;
    },
    
    onQuery: function() {
    	this.freshCategoryGrid();
    },
    
    onClearQuery: function() {
    	var nameList = this.__queryEditNameList;
    	for (var i = 0; i < nameList.length; i++) {
    		var name = nameList[i];
    		var edit = Ext.getCmp(name);
    		if (edit) {
    			edit.setValue(null);
    		}
    	}
    	
    	this.onQuery();
    },

    synUsers: function(){
        var me = this;
        var info = "确实要同步用户过来吗?";
        me.__page = 1;
        me.__total = 0;
        me.__limit = 100;
        ERP.MsgBox.confirm(info, function () {
            me.doSynUsers();
        });
    },
    doSynUsers: function(){
        var me = this;
        var el = Ext.getBody();
            el.mask("正在同步中...("+(me.__page * me.__limit)+"/"+me.__total+")");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Customer/batchSynUsers",
                method: "POST",
                params: {page:me.__page},
                callback: function (options, success, response) {
                    //el.unmask();
                    me.__page++;
                    if (success) {

                        var data = Ext.JSON.decode(response.responseText);
                        me.__total = data.data.total;
                        if(me.__total < me.__page * me.__limit){
                            ERP.MsgBox.showInfo("同步完成");
                            el.unmask();
                            return false;
                        }
                        if (data.success) {
                            me.doSynUsers();
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                            me.doSynUsers();
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            //window.location.reload();
                            me.doSynUsers();
                        });
                    }
                }

            });
    }
});