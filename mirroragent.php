<?php
02 
03 /*|----------------------------------------------------------|
04   |                                                          |
05   |                            EC2 Mirror                    |
06   |                                                          |
07   |            mirror_client.php    part 2 of 2              |
08   |                                                          |
09   |              copyleft Rk                                 |
10   |                                                          |
11   |----------------------------------------------------------|
12   |-------------------- what it does ------------------------|
13   |                                                          |
14   |   this script(s) will make an exact copy of an entire    |
15   |   directory with all subfolders and files on a remote    |
16   |   server.                                                |
17   |                                                          |
18   |   it connects to its sister script mirror_agent.php      |
19   |   on the remote server and receives the data as a hex    |
20   |   string. it converts this hex data to binary and        |
21   |   writes the files to disk.                              |
22   |                                                          |
23   |   after a sucessful run the mirror_agent.php and this    |
24   |   script will delete themselves to protect from abuse.   |
25   |                                                          |
26   |----------------------------------------------------------|
27   |                                                          |
28   |     =================================================    |
29   |     WARNING ! DON'T USE ORIGINAL ! WILL DELETE ITSELF    |
30   |     =================================================    |
31   |                                                          |
32   |----------------------------------------------------------|
33   |----------------------- usage ----------------------------|
34   |                                                          |
35   |   put a COPY of this file here INSIDE a fresh new        |
36   |   directory which must be WRITEABLE by PHP.              |
37   |                                                          |
38   |   make sure you UPLOADED a copy of the mirror_agent.php  |
39   |   to the directory on the remote server which            |
40   |   you want to mirror.                                    |
41   |                                                          |
42   |   CHANGE the $mirror_agent variable below to the         |
43   |   URL of the mirror_agent.php on the REMOTE SERVER.      |
44   |                                                          |
45   |   LOAD this file in your browser - done                  |
46   |                                                          |
47   |----------------------------------------------------------|*/
48  
49  
50 /* --------------------- configuration ---------------------- */
51  
52 // set this to the URL of the mirror_agent.php on the server you want to mirror
53 $mirror_agent = 'http://www.remote.host/the_directory_to_mirror/mirror_agent.php';
54  
55 /*|----------------------------------------------------------|
56   |                                                          |
57   |           no need to edit below this line                |
58   |                                                          |
59   |----------------------------------------------------------|*/
60  
61  
62 /* --------------------- functions -------------------------- */
63  
64 // convert hex to binary
65 function hex2asc($temp)
66 {
67     for ($i = 0; $i < strlen($temp); $i += 2) $data .= chr(hexdec(substr($temp,$i,2)));
68     return $data;
69 }
70  
71 // write file to disk
72 function file_write($filename, $filecontent, $mode='wb')
73 {
74     if($fp = fopen($filename,$mode))
75     {
76         fwrite($fp, $filecontent);
77         fclose($fp);
78         return true;
79     }else{
80         return false;
81     }
82 }
83  
84 /* --------------------- the code --------------------------- */
85  
86 // disable errors ( just in case you try this script before you read the usage above )
87 error_reporting(0);
88  
89 // disable time limit ( you never know how big this site is )
90 set_time_limit(0);
91  
92 // get the hex data from mirror
93 $cont = file_get_contents($mirror_agent);
94 $files = explode("\n",$cont);
95  
96 // check if it's really the mirror_agent and if we have write permission
97 if(trim($files[0]) == 'mirror_agent here' && is_writeable(dirname(__file__)))
98 {
99     // yes - start copying
100     for($i = 1; $i < count($files); $i++)
101     {
102         if(trim($files[$i]) != '')
103         {
104             $parts = explode('|',trim($files[$i]));
105             $permissions = intval($parts[2], 8);
106             if($parts[0] == 'dir')
107             {
108                 // it's a directory - make it
109                 mkdir($parts[1],$permissions);
110                 $done[] = $permissions.'|'.$parts[1];
111             }elseif($parts[0] == 'file')
112             {
113                 // it's a file - write it down
114                 file_write($parts[1], hex2asc($files[$i+1]));
115                 chmod($parts[1],$permissions);
116                 $done[] = $permissions.'|'.$parts[1];
117                 $i++;
118             }
119         }
120     }
121     // output status
122     echo '
123 <pre>
124 following files were copied:';
125  
126     for($i = 0; $i < count($done); $i++)
127     {
128         echo '
129 '.$done[$i];
130     }
131  
132     echo '
133 </pre>';
134     
135     unlink(__file__);
136 }else{
137     // error - it's not the mirror_agent, or you didn't chmod this directory
138     echo '
139 <pre>
140 ERROR:
141 '.$mirror_agent.'
142 does not respond like a mirror_agent should or
143 the directory '.dirname(__file__).' is not writeable.
144 </pre>';
145 }
146  
147 ?>