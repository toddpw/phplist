#!/usr/bin/perl
# generate a test file to import
open F,">testimport.txt";
print F "email\tName\tAddr1\tAddr2\tTown\tPostcode\tforeign key\tsend this user HTML emails\n";
$start = 33300;
for ($i=$start;$i<$start+10;$i++) {
  print F "$i\@reallynonexistentdomain.com\tTestUser $i\tAddr line 1\tAddr line2\tTown\tPostcode $i\tABC $i\t1\n";
}
close(F);

open F,">testimport2.txt";
$start = 0;
for ($i=$start;$i<$start+1000;$i++) {
  print F "test$i\@dev.quipu\n";
}
close(F);


