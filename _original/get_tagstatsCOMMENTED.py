#!/usr/bin/python

import pytumblr, sys, re, operator, datetime
reload(sys)
sys.setdefaultencoding("utf-8")

### SUBROUTINES

def addtocount(item, counter):
#    print "addtocount: " + str(item) 
    if item in counter:
#        print "count = " + str(counter[item])
        counter[item] = counter[item] + 1
    else:
#        print "count = 1"
        counter[item] = 1
#this uses an array to keep numbers of items (what kind of items? numbers? ids?)

def printpair(item, count, f):
#    print "printpair"
    f.write(item + ", " + str(count) + "\n")
#this formats and prints the number of an item from the counter array

def printall(typecount, tagcount, datecount, hourcount, threshold, outfile):

#    print "printall"
    f = open(outfile, 'w')

    f.write("******* TYPES:\n")
    sorttype = sorted(typecount.iteritems(), key=operator.itemgetter(1), reverse=True)
    for st in sorttype:
         printpair(st[0], st[1], f)

    f.write("\n\n******* DATES:\n")
    dates = sorted(datecount.keys())
    for d in dates:
         printpair(str(d), datecount[d], f)

    f.write("\n\n******* HOURS:\n")
    for hour in hourcount:
         printpair(str(hour), hourcount[hour], f)

    f.write("\n\n******* TAGS:\n")
    sorttag = sorted(tagcount.iteritems(), key=operator.itemgetter(1), reverse=True)
    for st in sorttag:
        if st[1] >= threshold:
                printpair(st[0], st[1], f)

    f.close()

def trytoprint(r, name, f):
#    print "trytoprint"
    try: 
        f.write(str(r[name]))
    except:
        print "couldn't write ", name
#        print r
    f.write(", ")
#print line to file, with fallback

def printpost(postdata, outfile):
#    print "printpost"
    r = postdata
    try:
        f = open(outfile, 'a')
    except:
        print "couldn't open outfile"
        return None
#    print 'trying to print post to outfile'

    trytoprint(r, 'id', f)
    trytoprint(r, 'blog_name', f)
    trytoprint(r, 'post_url', f)
    trytoprint(r, 'timestamp', f)
    trytoprint(r, 'date', f)
    trytoprint(r, 'note_count', f)  
    trytoprint(r, 'type', f)
    trytoprint(r, 'slug', f)
    trytoprint(r, 'tags', f)
    f.write('\n')

    f.close()
    

### MAIN FUNCTION

try:
    tagarg = sys.argv[1] 
    numposts = int(sys.argv[2])
    threshold = int(sys.argv[3])

    outfile = "output/" + tagarg + '_stats.csv'

    postfile = "output/" + tagarg + '_posts.csv'

    f = open(postfile, 'w')
    f.write('id, blog, url, timestamp, date, notes, type, slug, tags\n')
    f.close()

except:
    sys.exit('usage: get_tagstats.py <tagname> <numposts> <popularity threshold>')

client = pytumblr.TumblrRestClient(
    '<Tumblr API key>',
    '<Tumblr API secret>',
    '<oauth_token>',
    '<oauth_secret>',
)

timestamps = []
result = client.tagged(tagarg, limit=20)

# count up types and tags
typecount = {}
tagcount = {}
datecount = {}
hourcount = {}

for i in range(0, numposts/20):
#    print "i = " + str(i)
    sys.stderr.write(str(i))
    sys.stderr.write("\n")
    try:
        num = 0
        for r in result:
            num+= 1
            # timestamps
            try:
                ts = r['timestamp']
                timestamps.append(ts)
                d = datetime.date.fromtimestamp(ts)
                dt = datetime.datetime.fromtimestamp(ts)
                hour = dt.hour
                
                addtocount(d, datecount)
                addtocount(hour, hourcount)
            except:
                print 'failed to handle timestamp/date/hour count'

            # types and tags
            try:
                ty = r['type']
                addtocount(ty, typecount)
            except:
                print 'failed to handle type count'

            try:
                tags = r['tags']
            #        print tags
                for tag in tags:
                    tag = tag.lower()
#                    tag = re.sub(' ', '', tag)
                    addtocount(tag, tagcount)
            except:
                print 'failed to handle tag count'

            # if postfile then print post
            if postfile: 
                printpost(r, postfile)
    except:
        print "bad result: ", num
        break
    # find the earliest timestamp
    timestamps = sorted(timestamps)
    earliest = timestamps[0]
    # print earliest timestamp
    sys.stderr.write(str(earliest))
    sys.stderr.write("\n")

    printall(typecount, tagcount, datecount, hourcount, threshold, outfile)

    # fetch earlier results
    result = client.tagged(tagarg, limit=20, before=earliest)
    if not result:
        break



