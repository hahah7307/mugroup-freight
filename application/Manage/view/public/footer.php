
                <!-- 辅助元素，一般用于移动设备下遮罩 -->
                <div class="layadmin-body-shade" layadmin-event="shade"></div>
            </div>
        </div>

        <script>
            layui.config({
                base: '/static/layuiadmin/' //静态资源所在路径
            }).extend({
                index: 'lib/index' //主入口模块
            }).use('index');
        </script>
    </body>
</html>


