CREATE OR REPLACE VIEW view_purchase_ratio_price AS
select pu.id_purchase as fk_purchase, d.date, pu.fk_user, pay.fk_farm, (pay.amount-ifnull(j1.somme,0))/j2.nb_product_prix_poids as price
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

DROP TABLE IF EXISTS `purchase_ratio_price`;