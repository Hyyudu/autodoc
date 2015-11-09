<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 14.10.13
 * Time: 15:03
 * To change this template use File | Settings | File Templates.
 */
$pc = intval($_POST['power_consumption']);
echo "{state : '".($pc>200 ? 'inactive' : 'active'). "', power: $pc}";