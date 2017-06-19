// 拣货管理 - 主界面
Ext.define("ERP.Sale.PickMainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;
        me.__out_ref   = [];
        Ext.define("ERPPickBill", {
            extend: "Ext.data.Model",
            fields: ["id","order_sn", "ref", "bizDate", "customerName",'bill_status','ordertype','importDate','Delivery_time',
            'goods_money','good_code','barCode', 'good_name','g_count','sale_price','position_code','position_name',
                "consignee", "tel", "address","billStatus",  "amount", "delivery_date", 'delivery_time','bulk','goods_attr', 
                'apply_price','apply_count','apply_num','remark','supplier_name', 'stock', 'areaname','supplier_code']
        });
        var storePickBill = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPickBill",
            data: [],
            pageSize: 20,
            proxy: {
                type: "ajax",
                timeout:300000,
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Sale/PickBillList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });
        storePickBill.on("beforeload", function () {
            storePickBill.proxy.extraParams = me.getQueryParam();
        });
        storePickBill.on("load", function (e, records, successful) {
            if (successful) {
                me.gotoPickBillGridRecord(me.__lastId);
            }
        });

        //区域列表获取
        Ext.define("ERPSiteCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", {name: "cnt", type: "int"}]
        });
        var storeCategory = Ext.create("Ext.data.Store", {
            autoLoad: true,
            model: "ERPSiteCategory",
            data: [],
            pageSize: 100,
            proxy: {
                type: "ajax",
                actionMethods: {
                    read: "POST"
                },
                url: ERP.Const.BASE_URL + "Home/Site/lineList",
                reader: {
                    root: 'dataList',
                    totalProperty: 'totalCount'
                }
            }
        });

				show_table3 = [{
                    header: "商品编码",
                    dataIndex: "good_code",
                    width: 80,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "商品名称",
                    dataIndex: "good_name",
                    menuDisabled: true,
                    width:200,
                    sortable: false
                },{
                    header: "商品条码",
                    dataIndex: "barCode",
                    width: 150,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "计数单位",
                    dataIndex: "bulk",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "订货重量(大约)",
                    dataIndex: "goods_attr",
                    width: 90,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "订货份数",
                    dataIndex: "g_count",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "商品单价",
                    dataIndex: "goods_money",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "库位信息",
                    dataIndex: "position_name",
                    width:200,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "库存",
                    dataIndex: "stock",
                    width:100,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "供应商编码",
                    dataIndex: "supplier_code",
                    menuDisabled: true,
                    sortable: false,
                    width:200,
                    align: "right",
                },
                {
                    header: "供应商",
                    dataIndex: "supplier_name",
                    menuDisabled: true,
                    sortable: false,
                    width:200,
                    align: "right",
                }];
				show_table1 = [{
                    header: "状态",
                    dataIndex: "billStatus",
                    menuDisabled: true,
                    sortable: false,
                    width: 60,
                    renderer: function (value) {
                        return value == "已拣货" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                }, 
                {
                    header: "电商单号",
                    dataIndex: "order_sn",
                    width: 120,
                    menuDisabled: true,
                    sortable: false
                },

                {
                    header: "订单号",
                    dataIndex: "ref",
                    width: 120,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "订单类型",
                    dataIndex: "ordertype",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "收货人",
                    dataIndex: "consignee",
                    width: 150,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "收货地址",
                    dataIndex: "address",
                    width: 250,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "区域",
                    dataIndex: "areaname",
                    width: 50,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "收货电话",
                    dataIndex: "tel",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "导入日期",
                    dataIndex: "importDate",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "送货日期",
                    dataIndex: "delivery_date",
                    width: 80,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "送货时间",
                    dataIndex: "delivery_time",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "订单金额",
                    dataIndex: "amount",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },
                {
                    header: "备注",
                    dataIndex: "remark",
                    width: 300,
                    menuDisabled: true,
                    sortable: false
                },
                /*{
                    header: "拣货员编码",
                    dataIndex: "address",
                    width: 200,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "拣货员名称",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }, {
                    header: "终止人编码",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }, {
                    header: "终止人名称",
                    dataIndex: "amount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }*/];
				show_table2 = [
                /*
                {
                    header: "状态",
                    dataIndex: "billStatus",
                    menuDisabled: true,
                    sortable: false,
                    width: 60,
                    renderer: function (value) {
                        return value == "已拣货" ? "<span style='color:red'>" + value + "</span>" : value;
                    }
                },
                */
                {
                    header: "订单号",
                    dataIndex: "ref",
                    width: 120,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "商品名称",
                    dataIndex: "good_name",
                    width: 300,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "商品编号",
                    dataIndex: "good_code",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "商品条码",
                    dataIndex: "barCode",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "送货日期",
                    dataIndex: "delivery_date",
                    width: 80,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "送货时间",
                    dataIndex: "delivery_time",
                    width: 100,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "订单类型",
                    dataIndex: "ordertype",
                    menuDisabled: true,
                    sortable: false,
                    width: 60
                }, {
                    header: "计数单位",
                    dataIndex: "bulk",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "订货重量",
                    dataIndex: "goods_attr",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "订货份数",
                    dataIndex: "g_count",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "订货金额",
                    dataIndex: "goods_money",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                },{
                    header: "执行重量",
                    dataIndex: "apply_num",
                    width: 60,
                    menuDisabled: true,
                    sortable: false
                }, {
                    header: "执行份数",
                    dataIndex: "apply_count",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }, {
                    header: "执行单价",
                    dataIndex: "apply_price",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 60
                }];
 
        var gridPickBill = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            selModel: {
                mode: "MULTI"
            },
            selType: "checkboxmodel",
            id: 'show_table1',
            border: 0,
            autoScroll : true,
            title: "拣货汇总",
            columnLines: true,
            columns: show_table1,
            listeners: {
                select: {
                    fn: me.onPickBillGridSelect,
                    scope: me
                },
                itemdblclick: {
                    fn: me.onEditPickBill,
                    scope: me
                }
            },
            store: storePickBill,
            tbar: [{
                    id: "pagingToobar",
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePickBill,
                    totalProperty: 'totalCount',
                    displayInfo : true,
                    displayMsg: '显示第 {0} 条到 {1} 条记录，共{2}条数据',
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
                        data: [["1000"]]
                    }),
                    value: 1000,
                    listeners: {
                        change: {
                            fn: function () {
                                storePickBill.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                                storePickBill.currentPage = 1;
                                Ext.getCmp("pagingToobar").doRefresh();
                            },
                            scope: me
                        }
                    }
                }, {
                    xtype: "displayfield",
                    value: "条记录"
                }]
        });
        
       var gridPickBill2 = Ext.create("Ext.grid.Panel", {

            id: 'show_table2',
            border: 0,
            title: "拣货明细",
            columnLines: true,
            columns: show_table2,
            store: storePickBill,
            tbar: [{
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePickBill
                }]
        });        
       var gridPickBill3 = Ext.create("Ext.grid.Panel", {

            id:'show_table3',
            border: 0,
            title: "预拣货",
            columnLines: true,
            columns: show_table3,
            store: storePickBill,
            tbar: [{
                    xtype: "pagingtoolbar",
                    border: 0,
                    store: storePickBill
                }]
        });
        Ext.define("ERPPickBillDetail", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsCode", "goodsName", "goodsSpec", "unitName",
                "goodsCount", "goodsMoney", "goodsPrice"]
        });
        var storePickBillDetail = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPickBillDetail",
            data: []
        });
        var tbarEx = Ext.create('Ext.toolbar.Toolbar',{
            id : "tbarEx",
            items:[
                {
                    id: "goods_code",
                    labelWidth : 50,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "商品编码",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "only_code",
                    labelWidth : 50,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:140,
                    //fieldLabel : "唯一含有",
                    boxLabel: "只含有这个商品的订单",
                    margin: "5, 0, 0, 0",
                    xtype : "checkbox",
                    listeners: {
                        change: {
                            fn: function(obj, ischecked){
                                if(ischecked){
                                    Ext.getCmp("editBatchOutButton").enable();
                                } else {
                                    Ext.getCmp("editBatchOutButton").disable();
                                }
                            },
                            scope: me
                        },
                    },
                },
                {
                    id: "goods_bar",
                    labelWidth : 50,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "商品条码",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    xtype : "combo",
                    id : "sell_type",  
                    queryMode : "local",
                    editable : false,   
                    valueField : "id",
                    fieldLabel:'计数单位', 
                    labelWidth : 60,
                    labelAlign : "right",
                    width:120,
                    store : Ext.create("Ext.data.ArrayStore", {
                        fields : [ "id", "text" ],
                        data : [ [ "0", "所有" ], [ "1", "称重" ], [ "2", "按件" ] ]
                    }),
                    value: '0'
                }, 
                {
                    id: "supplier_name",
                    labelWidth : 40,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "供应商",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "address",
                    labelWidth : 40,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "地址",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "areaid",
                    fieldLabel: "路线",
                    labelWidth : 35,
                    xtype:"combo",
                    queryMode : "local",
                    editable : false,
                    valueField : "id",
                    displayField: "name",
                    name: "areaid",
                    width:100,
                    store : storeCategory,
                },
                {
                    id: "site_name",
                    labelWidth : 40,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "站点",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "order_sn",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:120,
                    fieldLabel : "单号",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "tel",
                    labelWidth : 30,
                    labelAlign : "right",
                    labelSeparator : "",
                    width:100,
                    fieldLabel : "手机",
                    margin: "5, 0, 0, 0",
                    xtype : "textfield"
                },
                {
                    id: "editSiteButton",
                    text : "配送修改",
                    iconCls : "ERP-button-ok",
                    handler : me.modifySite,
                    scope : me
                },
                {
                    id: "editBatchOutButton",
                    text : "批量出库",
                    iconCls : "ERP-button-ok",
                    handler : me.batchOut,
                    disabled:true,
                    scope : me
                },
            ]
        });
        me.tbarEx = tbarEx;
        var tbar = Ext.create('Ext.toolbar.Toolbar',{
            items:[
                {xtype:'checkboxgroup',name:'pick_status', id: 'pick_status', fieldLabel: '订单状态', labelWidth : 60,  labelAlign : "right",
                items: [  
                    { boxLabel: '待拣货', name: 'select_pick_status',width:60,inputValue: '1', checked: true},  
                    { boxLabel: '拣货中', name: 'select_pick_status',width: 60,  inputValue: '2'},  
                    { boxLabel: '拣货完成', name: 'select_pick_status',width:160,  inputValue: '3'}
                ]},
     {xtype:'radiogroup', name:'pick_type',id: 'pick_type', fieldLabel:'订单查询方式', labelWidth : 80,   labelAlign : "right",
                items: [  
                    { boxLabel: '汇总', name: 'rb',width: 60, inputValue: '1' ,id: 'radiogroup', checked: true},   
                    { boxLabel: '明细', name: 'rb',width: 60, inputValue: '2' },  
                    { boxLabel: '预拣', name: 'rb',width: 120, inputValue: '3' } 
                ],
                    listeners: {
                        change: me.refreshPickBillGrid
                    }
        },
{id: "start_time",xtype: "datefield",format: "Y-m-d",labelAlign: "right",labelSeparator: "",width : 210,fieldLabel: "送货日期 从",value:Ext.Date.format(me.GetDateStr(-1), 'Y-m-d')},
{id: "end_time",  xtype: "datefield",format: "Y-m-d",labelAlign: "right",labelSeparator: "",labelWidth : 8,width :120,fieldLabel: "到",value:Ext.Date.format(new Date(), 'Y-m-d')},  
{xtype : "combo",id : "delivery_time",width:60, queryMode : "local",editable : false,   valueField : "id",
                store : Ext.create("Ext.data.ArrayStore", {
                    fields : [ "id", "text" ],
                    data : [ [ "0", "全部" ], [ "1", "上午" ], [ "2", "下午" ] ]
                }),
                value: '0'
        },
                {
                    id: "order_time_start",
                    xtype: "datefield",
                    format: "Y-m-d H",
                    labelAlign: "left",
                    labelSeparator: "",
                    width : 170,
                    fieldLabel: "下单日期",
                    labelWidth:50,
                    value:""
                },
                {
                    id: "order_time_end",  
                    xtype: "datefield",
                    format: "Y-m-d H",labelAlign: "left",
                    labelSeparator: "",
                    labelWidth : 8,
                    width :130,
                    fieldLabel: "到",
                    value:Ext.Date.format(new Date(), 'Y-m-d H')
                }, 
                         
                {
                    text : "查询",
                    iconCls : "ERP-button-refresh",
                    handler : me.refreshPickBillGrid,
                    scope : me
                },               
                {
                    text : "导出",
                    iconCls : "ERP-button-exit",
                    handler : me.exportdata,
                    scope : me
                }
            ]
        });
        Ext.apply(me, {
        	
			layout : "border",
			border : 0,
            enableOverflow:true,
            xtype: "buttongroup",
            columns: 2,
            dockedItems: [tbar, tbarEx],
			//tbar : tbar,
            
            items: [{
                    region: "north",
                    height: "auto",
                    maxHeight:"500",
                    split: true,
                    layout: "fit",
                    border: 0,
                    //tbar:tbarEx,
                    items: [gridPickBill]
                },{
                    region: "north",
                    height: "auto",
                    maxHeight:"500",
                    split: true,
                    layout: "fit",
                    border: 0,
                    //tbar:tbarEx,
                    items: [gridPickBill2]
                },{
                    region: "north",
                    height: "auto",
                    maxHeight:"500",
                    split: true,
                    layout: "fit",
                    border: 0,
                    //tbar:tbarEx,
                    items: [gridPickBill3]
                }],
        });

        me.PickBillGrid = gridPickBill;
        //me.PickBillGrid2 = gridPickBill2;
        me.callParent(arguments);
        me.refreshPickBillGrid();
        me.initArealist();
    },
    refreshPickBillGrid: function (id) {

        var me = this;
    	var rb = Ext.getCmp("pick_type").getValue();
        pick_type = rb.rb;
    	Ext.getCmp("show_table1").hide();
    	Ext.getCmp("show_table2").hide();
    	Ext.getCmp("show_table3").hide();
    	Ext.getCmp("show_table" + pick_type).show();
        Ext.getCmp("pagingToobar").doRefresh();
      //汇总单无法使用商品搜索
      if(pick_type == 1){
        //Ext.getCmp("tbarEx").hide();
        Ext.getCmp("editSiteButton").show();
      } else {
        Ext.getCmp("editSiteButton").hide();
        //Ext.getCmp("tbarEx").show();
      }
      this.__lastId = id;

    },
    exportdata: function (id) {
        var rb = Ext.getCmp("pick_type").getValue();
        pick_type = rb.rb;
        //拣货状态
        var rb = Ext.getCmp("pick_status").getValue();
        pick_status = [rb.select_pick_status];
        var ext_params = "&goods_code="+Ext.getCmp("goods_code").getValue()+"&goods_bar="+Ext.getCmp("goods_bar").getValue()+"&supplier="+Ext.getCmp("supplier_name").getValue();
        ext_params+="&areaid="+Ext.getCmp("areaid").getValue()+"&site_name="+Ext.getCmp("site_name").getValue();
        
			send_parm = ext_params+'&start_time=' + Ext.Date.format(Ext.getCmp("start_time").getValue(), 'Y-m-d') + '&end_time=' + Ext.Date.format(Ext.getCmp("end_time").getValue(), 'Y-m-d')
				+'&delivery_time=' + Ext.getCmp("delivery_time").getValue() + '&sell_type=' + Ext.getCmp("sell_type").getValue() + '&order_sn=' + Ext.getCmp("order_sn").getValue()
				+'&pick_type=' + pick_type + '&pick_status=' + pick_status + '&page=1&start=0&limit=2000';
      //console.warn(url);
      url = ERP.Const.BASE_URL + "Home/Sale/PickBillList?act=export" + send_parm;
      console.warn(url);
			window.open(url);
    },
    onEditPickBill: function () {
        var item = this.PickBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的拣货管理单");
            return;
        }
        var PickBill = item[0];
        
        var form = Ext.create("ERP.Sale.WSEditForm", {
            parentForm: this,
            entity: PickBill
        });
        form.show();
        return;
        var info = "请确认是否设置单号: <span style='color:red'>" + PickBill.get("ref") + "</span> 为拣货完成?";
        var rb = Ext.getCmp("pick_type").getValue();

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/api/Pick",
                method: "POST",
                params: {id: PickBill.get("id"), pick_type:rb.rb},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                
      												Ext.getCmp("pagingToobar").doRefresh();;
                            });
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
        return;
    },

    onPickBillGridSelect: function () {
        this.refreshPickBillDetailGrid();
    },
    refreshPickBillDetailGrid: function (id) {return;
        var me = this;
        me.PickBillDetailGrid.setTitle("拣货管理单明细");
        var grid = me.PickBillGrid;
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        grid = me.PickBillDetailGrid;
        grid.setTitle("单号: " + bill.get("ref") + " 客户: "
                + bill.get("customerName") );
        var el = grid.getEl();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/PickBillDetailList",
            params: {
                billId: bill.get("ref")
            },
            method: "POST",
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
                        }
                    }
                }

                el.unmask();
            }
        });
    },
    refreshPickBillInfo: function () {
        var me = this;
        var item = me.PickBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var bill = item[0];

        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/refreshPickBillInfo",
            method: "POST",
            params: {
                id: bill.get("id")
            },
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    bill.set("amount", data.amount);
                    me.PickBillGrid.getStore().commitChanges();
                }
            }
        });
    },
    onCommit: function () {
        var me = this;
        var item = me.PickBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("没有选择要提交的拣货管理单");
            return;
        }
        var bill = item[0];

        var detailCount = this.PickBillDetailGrid.getStore().getCount();
        if (detailCount == 0) {
            ERP.MsgBox.showInfo("当前拣货管理单没有录入商品明细，不能提交");
            return;
        }

        var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref") + "</span> 的拣货管理单?";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("正在提交中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/commitPickBill",
                method: "POST",
                params: {id: bill.get("id")},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功完成提交操作", function () {
                                me.refreshPickBillGrid(data.id);
                            });
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
  
     getQueryParam: function() {
    	var me = this;
        var result = {
        	start_time: Ext.getCmp("start_time").getValue(),
        	end_time: Ext.getCmp("end_time").getValue(),
        	delivery_time: Ext.getCmp("delivery_time").getValue(),
        	sell_type: Ext.getCmp("sell_type").getValue(),
        	order_sn: Ext.getCmp("order_sn").getValue(),
            goods_code: Ext.getCmp("goods_code").getValue(),
            only_code: Ext.getCmp("only_code").getValue(),
            goods_bar: Ext.getCmp("goods_bar").getValue(),
            supplier: Ext.getCmp("supplier_name").getValue(),
            site: Ext.getCmp("site_name").getValue(),
            areaid:Ext.getCmp("areaid").getValue(),
            address:Ext.getCmp("address").getValue(),
            limit:Ext.getCmp("comboCountPerPage").getValue(),
            order_time_start : Ext.Date.format(Ext.getCmp("order_time_start").getValue(), "Y-m-d H:i:s"),
            order_time_end : Ext.Date.format(Ext.getCmp("order_time_end").getValue(), "Y-m-d H:i:s"),
            tel:Ext.getCmp("tel").getValue(),
        };//拣货查询方式
        var rb = Ext.getCmp("pick_type").getValue();
        result.pick_type = rb.rb;
        //拣货状态
        var rb = Ext.getCmp("pick_status").getValue();
        result.pick_status = [rb.select_pick_status];
        return result;
    }, 
    GetDateStr: function(AddDayCount)  {
        var dd = new Date();
        var dd_time = dd.getTime();
        dd_time = dd_time + AddDayCount * 24 * 3600 * 1000
        return new Date(dd_time);
    },  
    gotoPickBillGridRecord: function(id) {
        var me = this;
        var grid = me.PickBillGrid;
        grid.getSelectionModel().deselectAll();
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
    batchOut: function(){
        var me = this;
        
        var item = me.PickBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length <= 0) {
            ERP.MsgBox.showInfo("没有选择要出库的单子");
            return;
        }
        var goods_code = Ext.getCmp("goods_code").getValue();
        if(!goods_code){
            ERP.MsgBox.showInfo("请填写需要批量出库的商品编码");
            return false;
        }
        //读取信息
        var order_ref_arr = [];
        var order_ref = "";
        for(var i = 0 ; i < item.length ; i++){
            order_ref_arr.push(item[i].get("ref"));
        }
        for(var j = 0 ; j < order_ref_arr.length ; j++){
            order_ref =  order_ref_arr[j];
            if(Ext.Array.contains(me.__out_ref, order_ref)){
                continue;
            } else {
                break;
            }
        }
        //如果已经存在了，则退出
        if(Ext.Array.contains(me.__out_ref, order_ref)){
            ERP.MsgBox.showInfo("批量出库完成");
            me.refreshPickBillGrid();
            return false;
        }
        var len      = order_ref_arr.length;
        var deal_len = me.__out_ref.length + 1;

        var info = "请确认是否要执行批量出库操作，该操作执行后无法恢复订单状态";
        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask("(请勿关闭窗口)正在提交订单"+order_ref+"中...("+deal_len+"/"+len+")");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/batchOut",
                method: "POST",
                params: {order_ref: order_ref, goods_code:goods_code},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            //ERP.MsgBox.showInfo("成功完成订单"+order_ref+"的出库操作", function () {
                                me.__out_ref.push(order_ref);
                                //me.refreshPickBillGrid(data.id);
                                me.__batchOut();
                            //});
                        } else {
                            ERP.MsgBox.showInfo(data.msg+":订单号："+order_ref, function(){
                                me.__out_ref.push(order_ref);
                                me.__batchOut();
                            });
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
    __batchOut: function(){
        var me = this;
        
        var item = me.PickBillGrid.getSelectionModel().getSelection();
        if (item == null || item.length <= 0) {
            ERP.MsgBox.showInfo("没有选择要出库的单子");
            return;
        }
        var goods_code = Ext.getCmp("goods_code").getValue();
        if(!goods_code){
            ERP.MsgBox.showInfo("请填写需要批量出库的商品编码");
            return false;
        }
        //读取信息
        var order_ref_arr = [];
        var order_ref = "";
        for(var i = 0 ; i < item.length ; i++){
            order_ref_arr.push(item[i].get("ref"));
        }

        for(var j = 0 ; j < order_ref_arr.length ; j++){
            order_ref =  order_ref_arr[j];
            if(Ext.Array.contains(me.__out_ref, order_ref)){
                continue;
            } else {
                break;
            }
        }
        //如果已经存在了，则退出
        if(Ext.Array.contains(me.__out_ref, order_ref)){
            ERP.MsgBox.showInfo("批量出库完成");
            me.refreshPickBillGrid();
            return false;
        }
        var len      = order_ref_arr.length;
        var deal_len = me.__out_ref.length + 1;
            var el = Ext.getBody();
            el.mask("(请勿关闭窗口)正在提交订单"+order_ref+"中...("+deal_len+"/"+len+")");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Sale/batchOut",
                method: "POST",
                params: {order_ref: order_ref, goods_code:goods_code},
                callback: function (options, success, response) {
                    el.unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            //ERP.MsgBox.showInfo("成功完成订单"+order_ref+"的出库操作", function () {
                                me.__out_ref.push(order_ref);
                                //me.refreshPickBillGrid(data.id);
                                me.__batchOut();
                            //});
                        } else {
                            ERP.MsgBox.showInfo(data.msg+":订单号："+order_ref, function(){
                                me.__out_ref.push(order_ref);
                                me.__batchOut();
                            });
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误", function () {
                            window.location.reload();
                        });
                    }
                }
            });
    },
    modifySite: function(){
        var me= this;
        var grid = me.PickBillGrid;
        var item = this.PickBillGrid.getSelectionModel().getSelection();
        if(item.length == 0){
            alert("请至少选择一个单子");
            return false;
        }
        var form = Ext.create("ERP.Sale.SiteEditForm", {
            parentForm: this,
            entity: item
        });
        form.show();
        return;
        for(i = 0 ; i < item.length ; i++){
            var record = item[i];
        }

    },
    initArealist: function(){
        var me = this;
        Ext.Ajax.request({
            url : ERP.Const.BASE_URL + "Home/Site/lineList",
            params : {
                //id : Ext.getCmp("comboCA").getValue()
            },
            method : "POST",
            callback : function(options, success, response) {
                var combo = Ext.getCmp("areaid");
                var store = combo.getStore();

                store.removeAll();
                store.add({"id":0,"code":0,"name":"所有"});
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (store.getCount() > 0) {
                        combo.setValue(store.getAt(0).get("id"))
                    }
                }
            }
        });
    }
});