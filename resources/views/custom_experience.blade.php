<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
        <div class="container">
            <div class="content">
                <form action="/yunying/test/custom-experience" method="post" enctype="multipart/form-data">
                    <table>
                        <tr>
                            <td>
                                活动ID:
                            </td>
                            <td>
                                <input type="text" name="source_id">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                活动名：
                            </td>
                            <td>
                                <input type="text" name="source_name">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                得分乘的倍数
                            </td>
                            <td>
                                <input type="text" name="multiple">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                体验金有效天数
                            </td>
                            <td>
                                <input type="text" name="day">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                得分excel文件
                            </td>
                            <td>
                                <input type="file" name="xls_file">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><input type="submit" value="发奖"></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </body>
</html>
