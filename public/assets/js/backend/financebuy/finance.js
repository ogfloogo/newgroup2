define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'financebuy/finance/index' + location.search,
                    add_url: 'financebuy/finance/add',
                    edit_url: 'financebuy/finance/edit',
                    del_url: 'financebuy/finance/del',
                    multi_url: 'financebuy/finance/multi',
                    import_url: 'financebuy/finance/import',
                    table: 'finance',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'user_max_buy', title: __('User_max_buy')},
                        {field: 'user_min_buy', title: __('User_min_buy')},
                        {field: 'day', title: __('Day')},
                        {field: 'presell_day', title: __('Presell_day')},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'robot_status', title: __('Robot_status'), searchList: {"1":__('Robot_status 1'),"0":__('Robot_status 0')}, formatter: Table.api.formatter.status},
                        {field: 'robot_addorder_time_start', title: __('Robot_addorder_time_start')},
                        {field: 'robot_addorder_time_end', title: __('Robot_addorder_time_end')},
                        {field: 'robot_addorder_num_start', title: __('Robot_addorder_num_start')},
                        {field: 'robot_addorder_num_end', title: __('Robot_addorder_num_end')},
                        {field: 'auto_open', title: __('Auto_open'), searchList: {"1":__('Auto_open 1'),"0":__('Auto_open 0')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"0":__('Status 0')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    name: 'finance_rate',
                                    text: __('收益率配置'),
                                    title: function (row) {
                                        return row.name + ' ' + __('收益率配置');
                                    },
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    extend: 'data-area=\'["95%","90%"]\'',

                                    icon: 'fa ',
                                    url: 'financebuy/finance_rate/index?finance_id={row.id}',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }]
                        }                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'financebuy/finance/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'financebuy/finance/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'financebuy/finance/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
