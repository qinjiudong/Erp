// 站点档案 - 主界面
Ext.define("ERP.Site.MainForm", {
    extend: "Ext.panel.Panel",
    initComponent: function () {
        var me = this;

        Ext.define("ERPSiteLine", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "des", {name: "cnt", type: "int"}]
        });

        var lineGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "送货路线",
            features: [{ftype: "summary"}],
            forceFit: true,
            columnLines: true,
            columns: [
                {header: "路线编码", dataIndex: "code", width: 60, menuDisabled: true, sortable: false},
                {header: "路线名称", dataIndex: "name", flex: 1, menuDisabled: true, sortable: false,
                    summaryRenderer: function () {
                        return "站点个数合计";
                    }},
                {header: "站点个数", dataIndex: "cnt", width: 80, menuDisabled: true, sortable: false,
                    summaryType: "sum", align: "right"}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPSiteLine",
                autoLoad: false,
                data: []
            }),
            listeners: {
                select: {
                    fn: me.onLineGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditLine,
                    scope: me
                }
            }
        });
        me.lineGrid = lineGrid;

        Ext.define("ERPSiteCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "des", {name: "cnt", type: "int"}]
        });

        var categoryGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "送货区域",
            features: [{ftype: "summary"}],
            forceFit: true,
            columnLines: true,
            columns: [
                {header: "区域编码", dataIndex: "code", width: 60, menuDisabled: true, sortable: false},
                {header: "送货区域", dataIndex: "name", flex: 1, menuDisabled: true, sortable: false,
                    summaryRenderer: function () {
                        return "站点个数合计";
                    }},
                {header: "站点个数", dataIndex: "cnt", width: 80, menuDisabled: true, sortable: false,
                    summaryType: "sum", align: "right"}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPSiteCategory",
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

        Ext.define("ERPSite", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "contact01", "tel01", "mobile01", "qq01",
                "contact02", "tel02", "mobile02", "qq02", "categoryId", "initPayables",
                "initPayablesDT", "address", "addressShipping", "lineId"]
        });

        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPSite",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Site/siteList",
                reader: {
                    root: 'siteList',
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
                            me.gotoSiteGridRecord(me.__lastId);
                        }
                    },
                    scope: me
                }
            }
        });

        var siteGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            title: "站点列表",
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "站点编码", dataIndex: "code", menuDisabled: true, sortable: false},
                {header: "站点名称", dataIndex: "name", menuDisabled: true, sortable: false, width: 300},
                {header: "地址", dataIndex: "address", menuDisabled: true, sortable: false, width: 300},
                {header: "联系人", dataIndex: "contact01", menuDisabled: true, sortable: false},
                {header: "手机", dataIndex: "mobile01", menuDisabled: true, sortable: false},
                {header: "固话", dataIndex: "tel01", menuDisabled: true, sortable: false},
                {header: "QQ", dataIndex: "qq01", menuDisabled: true, sortable: false},
                {header: "备用联系人", dataIndex: "contact02", menuDisabled: true, sortable: false},
                {header: "备用联系人手机", dataIndex: "mobile02", menuDisabled: true, sortable: false},
                {header: "备用联系人固话", dataIndex: "tel02", menuDisabled: true, sortable: false},
                {header: "备用联系人QQ", dataIndex: "qq02", menuDisabled: true, sortable: false},
                {header: "发货地址", dataIndex: "addressShipping", menuDisabled: true, sortable: false, width: 300},
                {header: "应付期初余额", dataIndex: "initPayables", align: "right", xtype: "numbercolumn", menuDisabled: true, sortable: false},
                {header: "应付期初余额日期", dataIndex: "initPayablesDT", menuDisabled: true, sortable: false}
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
                    fn: me.onEditSite,
                    scope: me
                }
            }
        });


        me.siteGrid = siteGrid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "新增站点", iconCls: "ERP-button-add", handler: this.onAddSite, scope: this},
                {text: "新增送货区域", iconCls: "ERP-button-add-detail", handler: this.onAddCategory, scope: this},
                {text: "新增送货路线", iconCls: "ERP-button-add-detail", handler: this.onAddLine, scope: this},
                {text: "删除站点", iconCls:"ERP-button-delete", handler:this.onDeleteSite,scope: this}
            ],
            items: [{
            	region: "center", xtype: "container", layout: "border", border: 0,
            	items: [{
                    region: "center", xtype: "panel", layout: "fit", border: 0,
                    items: [siteGrid]
                },
                {
                    xtype: "panel",
                    region: "west",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [categoryGrid]
                },
                {
                    xtype: "panel",
                    region: "east",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [lineGrid]
                }

                ]
            }]
        });

        me.callParent(arguments);

        me.__queryEditNameList = ["editQueryCode", "editQueryName", "editQueryAddress", "editQueryContact", "editQueryMobile", 
                	                "editQueryTel", "editQueryQQ"];

        me.freshCategoryGrid();
    },
    onAddCategory: function () {
        var form = Ext.create("ERP.Site.CategoryEditForm", {
            parentForm: this
        });

        form.show();
    },
    onAddLine: function(){
        var form = Ext.create("ERP.Site.LineEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditCategory: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的送货区域");
            return;
        }

        var category = item[0];

        var form = Ext.create("ERP.Site.CategoryEditForm", {
            parentForm: this,
            entity: category
        });

        form.show();
    },
    onDeleteCategory: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的送货区域");
            return;
        }

        var category = item[0];
        var info = "请确认是否删除送货区域: <span style='color:red'>" + category.get("name") + "</span>";
        var me = this;

        var store = me.categoryGrid.getStore();
        var index = store.findExact("id", category.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Site/deleteCategory",
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
                    }
                }
            });
        });
    },
    freshCategoryGrid: function (id) {
    	var me = this;
        var grid = me.categoryGrid;
        var lineGrid = me.lineGrid;
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Site/categoryList",
            method: "POST",
            params: me.getQueryParam(),
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (id) {
                        var r = store.findExact("id", id);
                        if (r != -1) {
                            grid.getSelectionModel().select(r);
                        }
                    } else {
                        grid.getSelectionModel().select(0);
                    }
                }

                el.unmask();
            }
        });
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Site/lineList",
            method: "POST",
            params: me.getQueryParam(),
            callback: function (options, success, response) {
                var store = lineGrid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (id) {
                        var r = store.findExact("id", id);
                        if (r != -1) {
                            lineGrid.getSelectionModel().select(r);
                        }
                    } else {
                        lineGrid.getSelectionModel().select(0);
                    }
                }

                el.unmask();
            }
        });
    },
    freshSiteGrid: function (id) {
        var me = this;
        if("__parent" in me){

        } else {
            me.__parent = "category";
        }
        if(me.__parent == "category"){
            var item = this.categoryGrid.getSelectionModel().getSelection();
            if (item == null || item.length != 1) {
                var grid = this.siteGrid;
                grid.setTitle("站点档案");
                return;
            }

            var category = item[0];

            var grid = this.siteGrid;
            grid.setTitle("属于区域 [" + category.get("name") + "] 的站点");
        } else {
            var item = this.lineGrid.getSelectionModel().getSelection();
            if (item == null || item.length != 1) {
                var grid = this.siteGrid;
                grid.setTitle("站点档案");
                return;
            }

            var line = item[0];

            var grid = this.siteGrid;
            grid.setTitle("属于路线 [" + line.get("name") + "] 的站点");
        }
        

        this.__lastId = id;
        Ext.getCmp("pagingToolbar").doRefresh()
    },
    // private
    onCategoryGridSelect: function () {
        var me = this;
        me.siteGrid.getStore().currentPage = 1;
        me.__parent = "category";
        me.freshSiteGrid();
    },
    onLineGridSelect: function () {
        var me = this;
        me.siteGrid.getStore().currentPage = 1;
        me.__parent = "line";
        me.freshSiteGrid();
    },
    onAddSite: function () {
        if (this.categoryGrid.getStore().getCount() == 0) {
            ERP.MsgBox.showInfo("没有送货区域，请先新增送货区域");
            return;
        }

        var form = Ext.create("ERP.Site.SiteEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditSite: function () {
        var item = this.categoryGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择送货区域");
            return;
        }
        var category = item[0];

        var item = this.lineGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择路线");
            return;
        }
        var line = item[0];

        var item = this.siteGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的站点");
            return;
        }



        var site = item[0];
        site.set("categoryId", category.get("id"));
        site.set("lineId", line.get("id"));
        var form = Ext.create("ERP.Site.SiteEditForm", {
            parentForm: this,
            entity: site
        });

        form.show();
    },
    onDeleteSite: function () {
        var me = this;
        var item = me.siteGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的站点");
            return;
        }

        var site = item[0];

        var store = me.siteGrid.getStore();
        var index = store.findExact("id", site.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }


        var info = "请确认是否删除站点: <span style='color:red'>" + site.get("name") + "</span>";
        var me = this;
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在删除中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Site/deleteSite",
                method: "POST",
                params: {id: site.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshSiteGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    }
                }

            });
        });
    },
    gotoCategoryGridRecord: function (id) {
        var me = this;
        var grid = me.categoryGrid;
        var store = grid.getStore();
        if (id) {
            var r = store.findExact("id", id);
            if (r != -1) {
                grid.getSelectionModel().select(r);
            } else {
                grid.getSelectionModel().select(0);
            }
        }
    },
    gotoSiteGridRecord: function (id) {
        var me = this;
        var grid = me.siteGrid;
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
        category.set("cnt", me.siteGrid.getStore().getTotalCount());
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
        if("__parent" in me){

        } else {
            me.__parent = "category";
        }
        if(me.__parent == "category"){
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
        } else {
            var item = me.lineGrid.getSelectionModel().getSelection();
            var categoryId;
            if (item == null || item.length != 1) {
                categoryId = null;
            } else {
                categoryId = item[0].get("id"); 
            }

            var result = {
                lineId: categoryId
            };
        }
        
        return result;
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
    }
});