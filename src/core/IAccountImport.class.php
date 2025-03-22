<?php
/**
 * 账号导入接口
 * 定义批量导入账号信息的标准接口
 * Created: 2025-03-19
 * Arlvin
 */

interface IAccountImport {
    /**
     * 检测文件编码
     * @param string $content 文件内容
     * @return string 返回编码类型
     */
    public function detectEncoding($content);

    /**
     * 转换文件编码
     * @param string $content 要转换的内容
     * @param string $fromEncoding 原编码
     * @param string $toEncoding 目标编码
     * @return string 转换后的内容
     */
    public function convertEncoding($content, $fromEncoding, $toEncoding = 'UTF-8');

    /**
     * 验证CSV文件格式
     * @param array $headers CSV文件的表头
     * @return bool 返回验证结果
     */
    public function validateCSVFormat($headers);

    /**
     * 批量导入账号
     * @param array $accounts 账号数据数组
     * @return array 返回导入结果
     */
    public function importAccounts($accounts);
}
?>