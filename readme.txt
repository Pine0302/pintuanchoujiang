1.wsy_mark 中把抽奖团 is_show = 0
INSERT INTO `wsy_mark`.`collage_activities_explain_t`(`id`, `customer_id`, `isvalid`, `createtime`, `title`, `content`, `status`, `type`, `sort`, `type_name`, `isshow`)
VALUES (7, 41, b'1', '2019-12-09 06:36:12', '新抽奖团说明', '<p><span style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\">1.活动规则</span><br style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\"><span style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\">1.1、有活动的开始和截止的时间。</span><br style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\"><span style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\">1.2、可以设置几人成团，例如10人成团：首先购买的为团长，团长转发的邀请组团的链接发出去，别人可以参加这个团，其他人看到这个链接转发出去，一样可以邀请人过来参加这个团。到达10人后便是组团成功。</span><br style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\"><span style=\"color: rgb(51, 51, 51); font-family: \"Arial Normal\", Arial; font-size: 13px;\">1.3、可以设置抽奖，在组团成功的团中，设置好一定比例的中奖率或中奖团数（到底以团为算还是以人为算），中奖者发放中奖物资，其余的人全额退款。</span></p>', 7, 2, 0, '新抽奖团', 1)
2.ALTER TABLE `wsy_mark`.`collage_crew_order_t` ADD COLUMN `lottery_status` int(1) NULL DEFAULT 2 COMMENT '是否拼团抽中 0:未抽中  1:抽奖  2.等待抽奖';
  ALTER TABLE `wsy_mark`.`collage_group_order_t` ADD COLUMN `lottery_status` int(1) NULL DEFAULT 2 COMMENT '是否中奖 0:未中奖  1:已中奖 2:未开奖' ;
  ALTER TABLE `wsy_mark`.`collage_group_order_t` ADD COLUMN `group_id` int(11) NULL COMMENT '团id';

  ALTER TABLE `wsy_mark`.`collage_activities_t` ADD COLUMN `luck_split_money` int(11) NULL DEFAULT 0 COMMENT '拼团成功未中奖者平分的金额';

  CREATE TABLE `collage_lottery_count`  (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NULL DEFAULT NULL,
    `lottery_count` int(11) NULL DEFAULT NULL,
    `updatetime` int(11) NULL DEFAULT NULL,
    `customer_id` int(11) NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
  ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_bin ROW_FORMAT = Compact;


  CREATE TABLE `collage_lottery_recode`  (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NULL DEFAULT NULL,
    `batchcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
    `createtime` datetime(0) NULL DEFAULT NULL,
    `customer_id` int(11) NULL DEFAULT NULL,
    `group_id` int(11) NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
  ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_bin ROW_FORMAT = Compact;

  ALTER TABLE `wsy_mark`.`collage_group_order_t`
  ADD COLUMN `lottery_user_id` int(0) NULL DEFAULT 0 COMMENT '设定的中奖用户id' ;