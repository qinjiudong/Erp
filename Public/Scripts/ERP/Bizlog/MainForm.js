// 业务日志 - 主界面
Ext.define("ERP.Bizlog.MainForm", {
    extend: "Ext.panel.Panel",
    initComponent: function () {
        var me = this;
        Ext.define("ERPLog", {
            extend: "Ext.data.Model",
            fields: ["id", "loginName", "userName", "ip", "content", "dt", "logCategory"],
            idProperty: "id"
        });
        var store = Ext.create("Ext.data.Store", {
            model: "ERPLog",
            pageSize: 20,
            proxy: {
                type: "ajax",
                extraParams: {
                },
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Bizlog/logList",
                reader: {
                    root: 'logs',
                    totalProperty: 'totalCount'
                }
            },
            autoLoad: true
        });

        var grid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            loadMask: true,
            border: 0,
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 50}),
                {text: "登录名", dataIndex: "loginName", width: 60, menuDisabled: true},
                {text: "姓名", dataIndex: "userName", width: 80, menuDisabled: true},
                {text: "IP", dataIndex: "ip", width: 120, menuDisabled: true},
                {text: "日志分类", dataIndex: "logCategory", width: 150, menuDisabled: true},
                {text: "日志内容", dataIndex: "content", flex: 1, menuDisabled: true},
                {text: "日志记录时间", dataIndex: "dt", width: 140, menuDisabled: true}
            ],
            store: store,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
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
                                Ext.getCmp("pagingToobar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }],
            bbar: {
                xtype: "pagingtoolbar",
                store: store
            }
        });

        me.__grid = grid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "刷新", handler: me.onRefresh, scope: me, iconCls: "ERP-button-refresh"},
                "-",
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [
                {
                    region: "center", layout: "fit", xtype: "panel", border: 0,
                    items: [grid]
                }
            ]
        });

        me.callParent(arguments);
    },
    // private
    onRefresh: function () {
        Ext.getCmp("pagingToobar").doRefresh();
    }
});