# 3.2.x

## Bug fixes

 - PIM-8270: Update export jobs after a change on a channel category
 - PIM-8235: Do not store duplicate media file

## Technical improvement
DAPI-242: Improve queue to consume specific jobs

## BC breaks

 - Service `pim_catalog.saver.channel` class has been changed to `Akeneo\Channel\Bundle\Storage\Orm\ChannelSaver`.
