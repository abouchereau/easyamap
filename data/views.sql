CREATE OR REPLACE VIEW view_contract_purchaser AS
SELECT c.id_contract, pu.fk_user
FROM contract c
LEFT JOIN purchase pu ON pu.fk_contract =  c.id_contract
WHERE pu.fk_user IS NOT NULL
GROUP BY c.id_contract, pu.fk_user
ORDER BY c.id_contract;


CREATE OR REPLACE VIEW view_contract_nb_purchaser AS
SELECT c.id_contract, COUNT(DISTINCT(pu.fk_user)) AS nb_purchaser
FROM contract c
LEFT JOIN purchase pu ON pu.fk_contract =  c.id_contract
GROUP BY c.id_contract
ORDER BY c.id_contract;


CREATE OR REPLACE VIEW view_contract_conflict AS 
SELECT c1.id_contract AS id_contract,c2.label AS contrat,d.date AS distribution,CONCAT(ifnull(p.label,''),' ',ifnull(p.unit,'')) AS produit 
FROM contract c1, contract c2, contract_product cp1, contract_product cp2, distribution d, product p
WHERE c1.id_contract <> c2.id_contract 
AND cp2.fk_contract = c2.id_contract 
AND cp1.fk_contract = c1.id_contract 
AND cp1.fk_product = cp2.fk_product 
AND p.id_product = cp1.fk_product 
AND (d.date BETWEEN GREATEST(c1.period_start,c2.period_start) AND LEAST(c1.period_end,c2.period_end)) 
AND (
(c1.period_start BETWEEN c2.period_start AND c2.period_end) 
OR 
(c1.period_end BETWEEN c2.period_start AND c2.period_end) 
OR 
((c1.period_start < c2.period_start) AND (c1.period_end > c2.period_end))
)
ORDER BY c1.id_contract;




CREATE OR REPLACE VIEW view_contract_distribution_product AS 
SELECT c.id_contract AS fk_contract,pd.fk_distribution AS fk_distribution,pd.fk_product AS fk_product 
FROM product_distribution pd 
LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution 
LEFT JOIN contract c ON (d.date BETWEEN c.period_start AND c.period_end) 
JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
GROUP BY c.id_contract,pd.fk_distribution,pd.fk_product 
ORDER BY c.id_contract,pd.fk_distribution,pd.fk_product;


CREATE OR REPLACE VIEW view_deletable_distribution AS 
SELECT d.id_distribution AS fk_distribution 
FROM distribution d 
LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
WHERE pd.fk_distribution IS NULL;



CREATE OR REPLACE VIEW view_deletable_farm AS 
SELECT f.id_farm AS fk_farm 
FROM farm f 
LEFT JOIN product p ON p.fk_farm = f.id_farm 
LEFT JOIN referent r ON r.fk_farm = f.id_farm 
WHERE p.fk_farm IS NULL 
AND r.fk_farm IS NULL;



CREATE OR REPLACE VIEW view_deletable_product AS 
SELECT p.id_product AS fk_product 
FROM product p 
LEFT JOIN product_distribution pd ON pd.fk_product = p.id_product 
LEFT JOIN contract_product cp ON cp.fk_product = p.id_product
WHERE pd.fk_product IS NULL AND cp.fk_product IS NULL;


CREATE OR REPLACE VIEW view_deletable_user AS 
SELECT u.id_user AS fk_user 
FROM user u 
LEFT JOIN referent r ON r.fk_user = u.id_user 
LEFT JOIN purchase p ON p.fk_user = u.id_user 
WHERE r.fk_user IS NULL 
AND p.fk_user IS NULL;

CREATE OR REPLACE VIEW view_overage AS
SELECT pd.id_product_distribution, pd.fk_product, (SUM(p.quantity) - pd.max_quantity) AS excedent, pd.max_quantity
FROM product_distribution pd
LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
WHERE pd.max_quantity IS NOT NULL
GROUP BY pd.id_product_distribution
HAVING SUM(p.quantity) > pd.max_quantity ;

create or replace view view_deletable_contract as 
select id_contract as fk_contract from contract
where id_contract not in (
SELECT 
c.id_contract
FROM contract c
LEFT JOIN purchase p ON p.fk_contract =  c.id_contract
group by c.id_contract
having sum(p.quantity) is not null
and sum(p.quantity) >0);

CREATE OR REPLACE VIEW view_payment_purchase AS
select id_payment as fk_payment, pu.id_purchase as fk_purchase
from payment p
left join view_contract_distribution_product v1 on v1.fk_contract = p.fk_contract
left join product_distribution pd on pd.fk_distribution = v1.fk_distribution and pd.fk_product =  v1.fk_product
inner join purchase pu on pu.fk_product_distribution = pd.id_product_distribution and pu.fk_user = p.fk_user;



CREATE OR REPLACE VIEW view_purchase_ratio_price AS
select pu.id_purchase, d.date, pu.fk_user, pay.fk_farm, (pay.amount-ifnull(j1.somme,0))/j2.nb_product_prix_poids as prix_estime
from purchase pu
         left join payment pay on pu.fk_payment = pay.id_payment
         join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
         join distribution d on d.id_distribution = pd.fk_distribution
         join product pr on pr.id_product = pd.fk_product
         left join (
    select id_payment, fk_contract, fk_user, amount, round(sum(price),2) as somme from (
       select pay.id_payment, pay.fk_contract, pu.fk_user, pay.amount, pu.quantity*pd.price as price
       from purchase pu
                left join payment pay on pu.fk_payment = pay.id_payment
                left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                join product pr on pr.id_product = pd.fk_product
       where pr.ratio is null
   ) tt group by id_payment
) j1 on j1.id_payment = pay.id_payment and j1.fk_contract = pu.fk_contract
         left join (
    select pay.id_payment, pay.fk_contract, pay.fk_user, pay.amount, sum(pu.quantity) as nb_product_prix_poids
    from payment pay
             join purchase pu on pu.fk_payment = pay.id_payment
             join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
             join product pr on pr.id_product = pd.fk_product
    where pr.ratio is not null
    group by pay.id_payment
) j2 on j2.id_payment = pay.id_payment and j2.fk_contract = pu.fk_contract
where pr.ratio is not null
  and pay.fk_farm is not null;