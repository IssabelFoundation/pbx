#!/usr/bin/perl  
 
#make a tts dir inside your sounds dir (as specified below)  
#adjust the t2wp variable to point to your festival bin directory  
 
use Asterisk::AGI;  
use File::Basename;  
use Digest::MD5 qw(md5_hex);  
 
$AGI = new Asterisk::AGI;  
 
my %input = $AGI->ReadParse();  
my ($text)=@ARGV;  
my $hash = md5_hex($text);  
my $sounddir = "/var/lib/asterisk/sounds/tts";  
my $wavefile = "$sounddir/"."tts-$hash.wav";  
my $t2wp= "/usr/bin/";  
 
unless (-f $wavefile) {  
open(fileOUT, ">$sounddir"."/say-text-$hash.txt");  
print fileOUT "$text";  
close(fileOUT);  
my $execf=$t2wp."flite $sounddir/say-text-$hash.txt -F 8000 -o $wavefile";  
system($execf);  
unlink($sounddir."/say-text-$hash.txt");  
}  
$AGI->stream_file('tts/'.basename($wavefile,".wav"),1); 
