#! /bin/bash
if [[ $# -ne 2 ]]
then
	echo "Usage: $0 [List File] [Coupon File]"
	exit 140
fi

listFile=$1
declare -a couponList
couponList=(`cat "$2"`)

issueCount=0;
while IFS=";" read -r name email
do
	couponCode=${couponList[issueCount]};
	#echo "php resendCouponEmail.php \"$name\" \"$email\" \"$couponCode\"" 
	output=$(php resendCouponEmail.php "$name" "$email" "$couponCode")
	echo $output
	
	((issueCount++))
done <"$listFile"

echo "Total email issued: $issueCount"

