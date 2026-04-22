use strict;
use warnings;
my $file = $ARGV[0] or die "usage: $0 file\n";
open my $h, "<:raw", $file or die "open $file: $!";
my $s = do { local $/; <$h> };
close $h;

my $n1 = ($s =~ s{localhost/}{207.180.196.39/}g);
my $n2 = ($s =~ s{localhost\\/}{207.180.196.39\\/}g);
my $n3 = ($s =~ s{localhost\\\\/}{207.180.196.39\\\\/}g);

open my $o, ">:raw", $file or die "write $file: $!";
print $o $s;
close $o;

print "replaced plain: $n1, 1-bs: $n2, 2-bs: $n3\n";
